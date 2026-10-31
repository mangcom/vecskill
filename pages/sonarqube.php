<h1>การติดตั้งและใช้งาน SonarQube Community Edition</h1>
<p>
  SonarQube คือเครื่องมือวิเคราะห์คุณภาพโค้ด (Static Code Analysis) แบบ Self-hosted ที่ตรวจจับ Bug, Code Smell, ช่องโหว่ความปลอดภัยในซอร์สโค้ด, และวัด Code Coverage ได้
  โดยไม่ต้อง Run โปรแกรมจริง มี Web Dashboard แสดงผลสวยงามและตั้ง Quality Gate (เงื่อนไขผ่าน/ไม่ผ่าน) ให้ Pipeline ได้
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: เตรียม Host ก่อนติดตั้ง (สำคัญมาก!)</h2>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>ต้องทำขั้นตอนนี้ก่อนเสมอ ไม่งั้น Container จะ Crash ทันทีหลังเริ่ม:</strong> SonarQube ใช้ Elasticsearch ภายในสำหรับทำ Index ซึ่งต้องการค่า Kernel Parameter
  <code>vm.max_map_count</code> ที่สูงกว่าค่าเริ่มต้นของ Linux มาก
</p>
<p>ตั้งค่าแบบถาวร (มีผลหลัง Reboot ด้วย):</p>
<pre><code>echo "vm.max_map_count=524288" | sudo tee /etc/sysctl.d/99-sonarqube.conf
echo "fs.file-max=131072" | sudo tee -a /etc/sysctl.d/99-sonarqube.conf
sudo sysctl --system</code></pre>
<p>ตั้งค่า File Descriptor และ Process Limit ให้ Docker daemon (เผื่อ Host ของคุณจำกัดค่าต่ำไว้):</p>
<pre><code>sudo tee /etc/security/limits.d/99-sonarqube.conf > /dev/null <<'EOF'
root   -   nofile   131072
root   -   nproc    8192
EOF</code></pre>
<p>ตรวจสอบว่าค่าถูกตั้งแล้ว:</p>
<pre><code>sysctl vm.max_map_count
# ควรขึ้น vm.max_map_count = 524288</code></pre>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: ติดตั้งผ่าน Portainer Stack</h2>
<p>SonarQube ต้องใช้ฐานข้อมูล PostgreSQL แยกต่างหาก เราจะสร้างทั้งสอง Service ไว้ใน Stack เดียวกัน</p>

<h3>2.1 สร้าง Docker Volumes</h3>
<pre><code>docker volume create sonarqube_data
docker volume create sonarqube_extensions
docker volume create sonarqube_logs
docker volume create sonarqube_db_data</code></pre>

<h3>2.2 สร้าง Stack (`sonarqube-stack`)</h3>
<p>ไปที่ Portainer > Stacks > + Add stack แล้วใช้โค้ด Docker Compose นี้ (ปรับรหัสผ่านฐานข้อมูลให้ปลอดภัยกว่าตัวอย่าง):</p>
<pre><code>services:
  sonarqube:
    image: sonarqube:community
    container_name: sonarqube
    restart: unless-stopped
    depends_on:
      sonar_db:
        condition: service_healthy
    environment:
      SONAR_JDBC_URL: jdbc:postgresql://sonar_db:5432/sonar
      SONAR_JDBC_USERNAME: sonar
      SONAR_JDBC_PASSWORD: ChangeThisPassword#2025
    ports:
      - "9000:9000"
    volumes:
      - sonarqube_data:/opt/sonarqube/data
      - sonarqube_extensions:/opt/sonarqube/extensions
      - sonarqube_logs:/opt/sonarqube/logs
    networks:
      - app_net

  sonar_db:
    image: postgres:17
    container_name: sonar_db
    restart: unless-stopped
    environment:
      POSTGRES_USER: sonar
      POSTGRES_PASSWORD: ChangeThisPassword#2025
      POSTGRES_DB: sonar
    volumes:
      - sonarqube_db_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U sonar"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks:
      - app_net

networks:
  app_net:
    external: true

volumes:
  sonarqube_data:
    external: true
  sonarqube_extensions:
    external: true
  sonarqube_logs:
    external: true
  sonarqube_db_data:
    external: true
</code></pre>
<p>คลิก <strong>Deploy the stack</strong> แล้วรอ (SonarQube ใช้เวลาเริ่มต้นระบบประมาณ 1-2 นาที)</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ถ้า Container ยัง Restart วนไปมา:</strong> ให้เช็ค Log ผ่าน Portainer หรือ <code>docker logs sonarqube</code> ถ้าเจอ Error เกี่ยวกับ <code>max virtual memory areas</code> แปลว่ายังไม่ได้ตั้งค่า
  <code>vm.max_map_count</code> ในส่วนที่ 1 ให้กลับไปทำให้ครบก่อน
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: เข้าใช้งานครั้งแรก</h2>
<ol>
  <li>เปิดเว็บเบราว์เซอร์ไปที่ <code>http://[IP_SERVER_ของคุณ]:9000</code></li>
  <li>
    ล็อกอินด้วยข้อมูลเริ่มต้น:
    <ul>
      <li><strong>Username:</strong> <code>admin</code></li>
      <li><strong>Password:</strong> <code>admin</code></li>
    </ul>
  </li>
  <li>ระบบจะบังคับให้เปลี่ยนรหัสผ่านทันที <strong>(สำคัญมาก)</strong></li>
</ol>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: สแกนโปรเจกต์ครั้งแรก</h2>
<h3>4.1 สร้าง Project และ Token</h3>
<ol>
  <li>หลังล็อกอิน คลิก <strong>Create Project</strong> > <strong>Manually</strong></li>
  <li>ตั้งชื่อ Project Key และ Display Name (เช่น <code>vecskill-backend</code>)</li>
  <li>เลือก <strong>Use the global setting</strong> แล้วคลิก <strong>Set Up</strong></li>
  <li>เลือก <strong>Locally</strong> เพื่อสร้าง Token สำหรับยืนยันตัวตนตอนสแกน แล้ว <strong>คัดลอก Token เก็บไว้</strong> (จะเห็นได้ครั้งเดียว)</li>
</ol>

<h3>4.2 รัน Scanner (ผ่าน Docker ไม่ต้องติดตั้งอะไรเพิ่มบนเครื่อง)</h3>
<p>เข้าไปที่โฟลเดอร์โปรเจกต์ของคุณ (เช่นโปรเจกต์ Node.js/Express ที่พัฒนาไว้) แล้วรันคำสั่งนี้:</p>
<pre><code>docker run --rm --network app_net \
  -e SONAR_HOST_URL="http://sonarqube:9000" \
  -e SONAR_TOKEN="ใส่_TOKEN_ที่คัดลอกมา" \
  -v "$(pwd):/usr/src" \
  sonarsource/sonar-scanner-cli \
  -Dsonar.projectKey=vecskill-backend \
  -Dsonar.sources=.</code></pre>
<p>เมื่อสแกนเสร็จ กลับไปที่ Web UI ของ SonarQube แล้วรีเฟรชหน้า Project จะเห็นผลวิเคราะห์: จำนวน Bugs, Code Smells, Security Hotspots, และ Code Coverage</p>

<h3>4.3 (ทางเลือก) สร้างไฟล์ Config เพื่อไม่ต้องพิมพ์ Flag ซ้ำทุกครั้ง</h3>
<p>สร้างไฟล์ <code>sonar-project.properties</code> ไว้ที่ Root ของโปรเจกต์:</p>
<pre><code class="language-ini">sonar.projectKey=vecskill-backend
sonar.sources=.
sonar.exclusions=node_modules/**,dist/**
</code></pre>
<p>จากนั้นรันแค่:</p>
<pre><code>docker run --rm --network app_net \
  -e SONAR_HOST_URL="http://sonarqube:9000" \
  -e SONAR_TOKEN="ใส่_TOKEN_ที่คัดลอกมา" \
  -v "$(pwd):/usr/src" \
  sonarsource/sonar-scanner-cli</code></pre>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 5: การใส่ไว้ใน GitLab CI/CD Pipeline</h2>
<p>เก็บ URL และ Token ไว้ใน GitLab CI/CD Variables ก่อน (Settings > CI/CD > Variables ตั้งชื่อ <code>SONAR_TOKEN</code>) แล้วเพิ่ม Stage นี้ใน <code>.gitlab-ci.yml</code>:</p>
<pre><code class="language-yaml">code_quality:
  stage: test
  image: sonarsource/sonar-scanner-cli:latest
  tags:
    - docker
  variables:
    SONAR_HOST_URL: "http://sonarqube:9000" # หรือ domain ที่ตั้งผ่าน Caddy/NPM
  script:
    - sonar-scanner -Dsonar.projectKey=vecskill-backend -Dsonar.sources=.
  allow_failure: true
</code></pre>
