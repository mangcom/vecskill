<h1>การทำระบบ CI/CD ด้วย GitLab (Node.js + Docker + SSH Deploy)</h1>
<p>คู่มือนี้จะพาคุณสร้าง Pipeline เพื่อ Automate กระบวนการพัฒนาซอฟต์แวร์ ตั้งแต่การทดสอบ (Test), การสร้าง Docker Image (Build), และการนำขึ้น Server จริง (Deploy) โดยอัตโนมัติเมื่อมีการ Push โค้ด</p>
<div class="image-container">
  <div class="image-container">
    <img src="./images/CICD-01.jpg" alt="CI/CD" class="content-image" />
    <p>ภาพรวมการทำงาน: GitLab Runner Build Image -> Push to Registry -> SSH to Production -> Pull & Run</p>
  </div>
</div>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: เตรียมความพร้อม (Infrastructure)</h2>

<h3>1.1 ตั้งค่า Insecure Registry (สำคัญมาก)</h3>
<p>
  เนื่องจากเราใช้ Registry ภายใน (<code>registry.vecskill.ovec</code>) แบบไม่มี SSL Certificate ที่ถูกต้อง เราต้องบอกให้ Docker ทั้งฝั่ง <strong>Runner</strong> และฝั่ง
  <strong>Production</strong> ยอมรับมัน
</p>
<p>แก้ไขไฟล์ <code>/etc/docker/daemon.json</code> (ถ้าไม่มีให้สร้างใหม่):</p>
<pre><code class="language-json">{
  "insecure-registries" : ["registry.vecskill.ovec", "registry-ui.vecskill.ovec"]
}</code></pre>
<p>จากนั้นรีสตาร์ท Docker:</p>
<pre><code>sudo systemctl restart docker</code></pre>

<h3>1.2 สร้าง SSH Key สำหรับ Deploy</h3>
<p>เราต้องสร้างกุญแจเพื่อให้ GitLab Runner สามารถ SSH เข้าไปวางไฟล์และรันคำสั่งที่เครื่อง Production ได้โดยไม่ต้องใช้รหัสผ่าน</p>
<ol>
  <li>
    <strong>ที่เครื่อง DevOps (หรือเครื่องของคุณ):</strong> สร้าง Key
    <pre><code>ssh-keygen -t ed25519</code></pre>
    (กด Enter ไปเรื่อยๆ ไม่ต้องใส่ Passphrase)
  </li>
  <li>
    <strong>ดู Public Key (ตัวแม่กุญแจ):</strong>
    <pre><code>cat ~/.ssh/id_ed25519.pub</code></pre>
    <em>ผลลัพธ์: <code>ssh-ed25519 AAAAC3NzaC... root@devops</code></em>
  </li>
  <li>
    <strong>นำ Public Key ไปใส่ที่เครื่อง Production:</strong>
    <ul>
      <li>SSH เข้าไปที่เครื่อง Production</li>
      <li>แก้ไขไฟล์ <code>~/.ssh/authorized_keys</code></li>
      <li>วาง Public Key ที่ได้ต่อท้ายบรรทัดเดิม</li>
    </ul>
  </li>
  <li>
    <strong>ดู Private Key (ตัวลูกกุญแจ - เก็บเป็นความลับ):</strong>
    <pre><code>cat ~/.ssh/id_ed25519</code></pre>
    <em>ผลลัพธ์: <code>-----BEGIN OPENSSH PRIVATE KEY----- ตัวคีย์-----END OPENSSH PRIVATE KEY-----</code> (คัดลอกเก็บไว้ทั้งหมดเพื่อใช้ในขั้นตอนต่อไป)</em>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: ตั้งค่า GitLab Project</h2>

<h3>2.1 สร้าง Project และตั้งค่า Variable</h3>
<ol>
  <li>สร้าง Blank Project ชื่อ <code>vecskill</code></li>
  <li>ไปที่ <strong>Settings -> CI/CD -> Variables</strong></li>
  <li>
    กด <strong>Add variable</strong>
    <ul>
      <li><strong>Type:</strong> Variable</li>
      <li><strong>Visibility:</strong> Variable</li>
      <li><strong>Key:</strong> <code>SSH_PRIVATE_KEY</code></li>
      <li><strong>Value:</strong> (วาง Private Key -----BEGIN OPENSSH PRIVATE KEY----- ... ที่คัดลอกมาทั้งหมด)</li>
      <li><strong>Flags:</strong> เลือก Protect variable</li>
    </ul>
  </li>
</ol>

<h3>2.2 สร้าง Runner</h3>
<ol>
  <li>ไปที่ <strong>Settings -> CI/CD -> Runners -> New project runner</strong></li>
  <li><strong>Tags:</strong> ใส่คำว่า <code>docker</code> (ต้องตรงกับในไฟล์ .gitlab-ci.yml)</li>
  <li>นำ Token ไป Register ที่เครื่อง Runner (ดูวิธีในการติดตั้ง Gitlab Runner)</li>
</ol>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: เตรียมไฟล์โปรเจกต์ (Coding)</h2>

<h3>3.1 Dockerfile</h3>
<p>สร้างไฟล์ <code>Dockerfile</code> สำหรับ Node.js App:</p>
<pre><code class="language-dockerfile">FROM node:22
WORKDIR /app
# Copy package.json ก่อนเพื่อใช้ Cache
COPY package*.json ./
RUN npm install
COPY . .

EXPOSE 80 
CMD ["node", "index.js"]
</code></pre>

<h3>3.2 docker-compose.yaml (สำหรับ Production)</h3>
<p>ไฟล์นี้จะถูกส่งไปรันที่เครื่อง Production</p>
<pre><code class="language-yaml">services:
  mysql:
    image: mysql:8.0
    container_name: mysql2
    environment:
      MYSQL_ROOT_PASSWORD: 1234        # รหัสผ่าน root
      MYSQL_DATABASE: yourdatabase     # ชื่อ database เริ่มต้น
      MYSQL_USER: devops               # ผู้ใช้ใหม่
      MYSQL_PASSWORD: 1234             # รหัสผ่านของผู้ใช้ใหม่
    ports:
      - "3306:3306"                           # พอร์ต MySQL
    volumes:
      - mysql_data:/var/lib/mysql             # เก็บข้อมูลไว้ถาวรในโฟลเดอร์ host
      # - ./init_db:/docker-entrypoint-initdb.d   # auto run SQL ที่นี่
    command: []   # ไม่ต้องใส่อะไร ปล่อย default

  phpmyadmin:
    image: phpmyadmin:latest
    container_name: phpmyadmin2
    ports:
      - "8080:80"                      # เปิดหน้าเว็บ phpMyAdmin ผ่าน http://localhost:8080
    environment:
      PMA_HOST: mysql                  # ชื่อ service ของ MySQL (ต้องตรงกับด้านบน)
      PMA_USER: root
      PMA_PASSWORD: 1234
      UPLOAD_LIMIT: 64M
    depends_on:
      - mysql  

  myservice:
    image: registry.vecskill.ovec/my-image:latest
    ports:
      - "81:80"
    environment:
      #PORT: 80
      JWT_SECRET: jwtsecretekey
      JWT_REFRESH_SECRET: jwtsecreatekeyrefresh
      ROOT_USERNAME: admin
      ROOT_PASSWORD: 1234
      MYSQL_HOST: localhost
      MYSQL_PORT: 3306
      MYSQL_DB: yourdatabase
      MYSQL_USERNAME: devops
      MYSQL_PASSWORD: 1234
    profiles:
      - production
      - backend

volumes:
  mysql_data:
</code></pre>

<h3>3.3 .gitlab-ci.yml (Pipeline Config)</h3>
<p>ไฟล์กำหนดขั้นตอนการทำงานของ CI/CD</p>
<pre><code class="language-yaml">stages:
  - test
  - build
  - deploy

variables:
  # แก้ไข IP เครื่อง Production ให้ถูกต้อง
  PRODUCTION_IP: "192.168.100.102"
  PRODUCTION_USER: "root"
  IMAGE_NAME: "registry.vecskill.ovec/my-image:latest"

# --- TEST STAGE ---
test_job:
  stage: test
  image: node:22
  tags:
    - docker
  script:
    - npm install
    # - npm test (ถ้ามี)

# --- BUILD STAGE ---
build:
  stage: build
  image: docker:24
  services:
    - docker:24-dind
  tags:
    - docker
  variables:
    DOCKER_TLS_CERTDIR: ""
  script:
    - docker info
    - docker build -t $IMAGE_NAME .
    - docker push $IMAGE_NAME
  only:
    - main

# --- DEPLOY STAGE ---
deploy_to_production:
  stage: deploy
  image: alpine:latest
  tags:
    - docker
  before_script:
    # ติดตั้ง SSH Client และเตรียม Key
    - apk add --no-cache openssh-client
    - mkdir -p ~/.ssh
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - ssh-keyscan -H $PRODUCTION_IP >> ~/.ssh/known_hosts
  script:
    # 1. ส่งไฟล์ docker-compose ไปที่ Server
    - scp -i ~/.ssh/id_ed25519 -r ./docker-compose.yaml $PRODUCTION_USER@$PRODUCTION_IP:/opt/docker-compose.yaml
    
    # 2. Remote SSH ไปสั่ง Pull และ Up
    - |
      ssh -i ~/.ssh/id_ed25519 $PRODUCTION_USER@$PRODUCTION_IP "
        cd /opt &&
        docker compose pull &&
        docker compose --profile production up -d --remove-orphans
      "
  only:
    - main
</code></pre>
<hr style="margin: 40px 0" />

<h2>เจาะลึกการทำงานของ .gitlab-ci.yml ทีละส่วน</h2>
<p>ด้านล่างนี้คือคำอธิบายรายละเอียดของ Code ที่เราใช้ ว่าแต่ละบรรทัดมีหน้าที่อะไร และทำไมต้องเขียนแบบนี้</p>

<h3>1. การประกาศตัวแปร (Variables)</h3>
<p>เรากำหนดค่าคงที่ไว้ด้านบนสุด เพื่อให้แก้ไขได้ง่ายในจุดเดียว</p>
<table class="spec-table">
  <thead>
    <tr>
      <th>ตัวแปร</th>
      <th>ค่าตัวอย่าง</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>PRODUCTION_IP</code></td>
      <td>192.168.100.102</td>
      <td>IP Address ของเครื่อง Server ปลายทางที่เราจะ Deploy งานไปหา</td>
    </tr>
    <tr>
      <td><code>PRODUCTION_USER</code></td>
      <td>root</td>
      <td>User ที่จะใช้ SSH เข้าไป (แนะนำเป็น root หรือ user ที่มีสิทธิ์ docker)</td>
    </tr>
    <tr>
      <td><code>IMAGE_NAME</code></td>
      <td>registry.vecskill.ovec/my-image:latest</td>
      <td>
        ชื่อเต็มของ Docker Image ที่ประกอบด้วย: <br />
        <code>[Registry URL]</code> / <code>[Image Name]</code> : <code>[Tag]</code>
      </td>
    </tr>
  </tbody>
</table>

<h3>2. Build Stage (สร้างและส่ง Docker Image)</h3>
<p>หัวใจสำคัญคือการใช้เทคนิค <strong>Docker-in-Docker (dind)</strong></p>
<ul>
  <li>
    <strong><code>image: docker:24</code></strong
    >: ใช้ Docker Client เวอร์ชัน 24 เป็นตัวรันคำสั่ง
  </li>
  <li>
    <strong><code>services: docker:24-dind</code></strong
    >: เป็น Service พิเศษที่เปิด Docker Daemon ซ้อนขึ้นมาภายใน Container เพื่อให้เราสั่ง <code>docker build</code> ได้
  </li>
  <li>
    <strong><code>DOCKER_TLS_CERTDIR: ""</code></strong
    >: <span style="color: red">(สำคัญ)</span> สั่งปิดการเข้ารหัส TLS ระหว่าง Client กับ Daemon ภายใน เพราะเราใช้ในเครือข่ายภายใน การเปิด TLS อาจทำให้เกิด Error ใบรับรองไม่ผ่าน
  </li>
  <li>
    <strong><code>docker push $IMAGE_NAME</code></strong
    >: ส่ง Image ที่สร้างเสร็จแล้วไปเก็บไว้ที่ Registry กลาง (registry.vecskill.ovec) เพื่อรอให้ Production ดึงไปใช้
  </li>
</ul>

<h3>3. Deploy Stage (นำขึ้น Server จริง)</h3>
<p>ขั้นตอนนี้จะใช้ <strong>Alpine Linux</strong> ขนาดเล็ก เพื่อ SSH ไปสั่งงานเครื่องปลายทาง</p>

<h4>3.1 ส่วนเตรียมความพร้อม (before_script)</h4>
<table class="spec-table">
  <tr>
    <td style="width: 40%"><code>apk add openssh-client</code></td>
    <td>ติดตั้งโปรแกรม SSH เพราะ Alpine ไม่มีมาให้ตั้งแต่ต้น</td>
  </tr>
  <tr>
    <td><code>echo "$SSH_PRIVATE_KEY" | tr -d '\r' ...</code></td>
    <td>
      นำ Private Key ที่ซ่อนไว้ใน GitLab Variable มาสร้างไฟล์ <code>id_ed25519</code><br />
      * คำสั่ง <code>tr -d '\r'</code> ช่วยลบตัวอักษรขึ้นบรรทัดใหม่แบบ Windows ที่อาจติดมา ซึ่งมักทำให้ Key ใช้ไม่ได้
    </td>
  </tr>
  <tr>
    <td><code>chmod 600 ...</code></td>
    <td>กำหนดสิทธิ์ไฟล์ Key ให้ <strong>"อ่านได้เฉพาะเจ้าของ"</strong> (ถ้าไม่ทำ SSH จะปฏิเสธการใช้งานทันที)</td>
  </tr>
  <tr>
    <td><code>ssh-keyscan ... >> known_hosts</code></td>
    <td>
      บันทึกลายนิ้วมือ (Fingerprint) ของ Server ปลายทางไว้ เพื่อป้องกันไม่ให้ SSH ถามว่า
      <em>"Are you sure you want to connect?"</em> ซึ่งจะทำให้ Pipeline ค้าง
    </td>
  </tr>
</table>

<h4>3.2 ส่วนคำสั่งหลัก (script)</h4>
<p>เราแบ่งการทำงานเป็น 2 ขยัก:</p>
<ol>
  <li>
    <strong>ส่งไฟล์ Config (SCP):</strong><br />
    <code>scp ... docker-compose.yaml ...</code><br />
    เราส่งไฟล์ <code>docker-compose.yaml</code> ล่าสุดไปที่ Server เพื่อให้มั่นใจว่า Config ตรงกับใน Code ของเรา
  </li>
  <li>
    <strong>สั่งรันคำสั่ง (SSH):</strong><br />
    <code>docker compose --profile production up -d --remove-orphans</code><br />
    <ul>
      <li><code>pull</code>: ดึง Image ล่าสุดมาจาก Registry</li>
      <li><code>--profile production</code>: (จุดที่แก้ไขเพิ่ม) สั่งรันเฉพาะ Service ที่ระบุ profile: production ไว้ในไฟล์ yaml</li>
      <li><code>up -d</code>: เริ่มต้น Container ในโหมด Background</li>
      <li><code>--remove-orphans</code>: ลบ Container เก่าที่ไม่ได้ระบุในไฟล์ docker-compose แล้วทิ้งไป (เคลียร์ขยะ)</li>
    </ul>
  </li>
</ol>

<h1>คำอธิบาย .gitlab-ci.yml แบบบรรทัดต่อบรรทัด (Full Detail)</h1>
<p>ตารางด้านล่างนี้จะอธิบาย Code ทุกบรรทัดโดยละเอียด ไม่มีการตัดทอน เพื่อให้เข้าใจหน้าที่ของแต่ละบรรทัดอย่างแท้จริง</p>

<hr style="margin: 40px 0" />

<h3>ส่วนที่ 1: การกำหนดลำดับและตัวแปร (Stages & Variables)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th style="width: 40%">Code (คำสั่งเต็ม)</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>stages:</code></td>
      <td><strong>เริ่มการประกาศลำดับขั้นตอน (Stages):</strong> บอก GitLab ว่า Pipeline นี้จะมีขั้นตอนหลักอะไรบ้าง เรียงลำดับจากบนลงล่าง</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;- test</code></td>
      <td><strong>ขั้นตอนที่ 1:</strong> ชื่อว่า "test" (จะทำเป็นอันดับแรกสุด)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;- build</code></td>
      <td><strong>ขั้นตอนที่ 2:</strong> ชื่อว่า "build" (จะทำก็ต่อเมื่อขั้นตอน test ผ่านสำเร็จเท่านั้น)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;- deploy</code></td>
      <td><strong>ขั้นตอนที่ 3:</strong> ชื่อว่า "deploy" (จะทำก็ต่อเมื่อขั้นตอน build ผ่านสำเร็จเท่านั้น)</td>
    </tr>
    <tr>
      <td><code>variables:</code></td>
      <td><strong>เริ่มการประกาศตัวแปร Global:</strong> ตัวแปรที่ประกาศตรงนี้ จะสามารถเรียกใช้ได้จากทุก Job ในทุก Stage</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;PRODUCTION_IP: "192.168.100.102"</code></td>
      <td>สร้างตัวแปรชื่อ <strong>PRODUCTION_IP</strong> เก็บค่า IP Address ของเครื่อง Server ปลายทางไว้ เพื่อให้แก้ไขง่ายในที่เดียว</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;PRODUCTION_USER: "root"</code></td>
      <td>สร้างตัวแปรชื่อ <strong>PRODUCTION_USER</strong> เก็บชื่อ User ที่จะใช้ SSH เข้าไป (ในที่นี้ใช้ root)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;IMAGE_NAME: "registry.vecskill.ovec/my-image:latest"</code></td>
      <td>สร้างตัวแปรชื่อ <strong>IMAGE_NAME</strong> เก็บชื่อเต็มของ Docker Image รวมถึง Registry URL และ Tag เพื่อใช้ทั้งตอน Build และ Deploy ให้ตรงกัน</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h3>ส่วนที่ 2: Test Stage (Job: test_job)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th style="width: 40%">Code (คำสั่งเต็ม)</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>test_job:</code></td>
      <td><strong>ประกาศชื่อ Job:</strong> ตั้งชื่อ Job นี้ว่า "test_job" (ชื่อนี้จะแสดงในหน้าเว็บ GitLab)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;stage: test</code></td>
      <td><strong>ระบุ Stage:</strong> กำหนดให้ Job นี้อยู่ในขั้นตอน "test" (ตามที่ประกาศไว้บนสุด)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;image: node:22</code></td>
      <td><strong>กำหนด Environment:</strong> สั่งให้ GitLab Runner ใช้ Docker Image ชื่อ <code>node:22</code> มาจำลองสภาพแวดล้อม เพราะเราต้องการใช้คำสั่ง <code>npm</code></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;tags:</code></td>
      <td><strong>เริ่มการระบุ Runner:</strong> บอกว่าจะส่งงานนี้ไปให้ Runner ตัวไหนทำ</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker</code></td>
      <td><strong>ระบุ Tag:</strong> เลือก Runner ที่มี Tag ชื่อ "docker" (ต้องตรงกับตอนที่เรา Register Runner)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;script:</code></td>
      <td><strong>เริ่มชุดคำสั่ง:</strong> ส่วนนี้คือคำสั่ง Linux จริงๆ ที่จะให้รัน</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- npm install</code></td>
      <td><strong>คำสั่งที่ 1:</strong> สั่งติดตั้ง Dependencies ตามไฟล์ package.json เพื่อตรวจสอบว่าไฟล์โปรเจกต์สมบูรณ์หรือไม่</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;# - npm test</code></td>
      <td>(บรรทัดนี้ถูก Comment ไว้) ถ้าเปิดใช้งาน จะเป็นการสั่งรัน Unit Test ของโปรเจกต์</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h3>ส่วนที่ 3: Build Stage (Job: build)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th style="width: 40%">Code (คำสั่งเต็ม)</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>build:</code></td>
      <td><strong>ประกาศชื่อ Job:</strong> ตั้งชื่อ Job นี้ว่า "build"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;stage: build</code></td>
      <td><strong>ระบุ Stage:</strong> กำหนดให้ Job นี้อยู่ในขั้นตอน "build"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;image: docker:24</code></td>
      <td><strong>กำหนด Environment:</strong> ใช้ Docker Image ชื่อ <code>docker:24</code> (เวอร์ชันนี้มีแค่ Client Tools สำหรับสั่งงาน Docker)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;services:</code></td>
      <td><strong>บริการเสริม (Services):</strong> เรียกใช้ Container พิเศษมาช่วยงาน</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker:24-dind</code></td>
      <td><strong>Docker-in-Docker:</strong> รัน Docker Daemon ซ้อนขึ้นมา เพื่อให้เราสามารถสั่ง <code>docker build</code> ภายใน Job นี้ได้</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;tags:</code></td>
      <td><strong>เริ่มการระบุ Runner:</strong> (ต้องประกาศซ้ำทุก Job)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker</code></td>
      <td><strong>ระบุ Tag:</strong> ส่งงานไปหา Runner ที่มี Tag "docker"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;variables:</code></td>
      <td><strong>ตัวแปรเฉพาะ Job:</strong> ประกาศตัวแปรที่จะใช้แค่ใน Job นี้เท่านั้น</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;DOCKER_TLS_CERTDIR: ""</code></td>
      <td><strong>ปิด TLS:</strong> สั่งให้ Docker Client คุยกับ Docker Daemon แบบไม่เข้ารหัส (HTTP) เพื่อแก้ปัญหา Certificate Error ในระบบภายใน</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;script:</code></td>
      <td><strong>เริ่มชุดคำสั่ง:</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker info</code></td>
      <td><strong>คำสั่งที่ 1:</strong> แสดงข้อมูล Docker เพื่อเช็คว่าเชื่อมต่อกับ Service dind ได้สำเร็จหรือไม่ (ใช้ Debug)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker build -t $IMAGE_NAME .</code></td>
      <td><strong>คำสั่งที่ 2:</strong> สั่งสร้าง (Build) Docker Image จากไฟล์ Dockerfile ในโฟลเดอร์ปัจจุบัน (.) และตั้งชื่อตามตัวแปร <code>$IMAGE_NAME</code></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker push $IMAGE_NAME</code></td>
      <td><strong>คำสั่งที่ 3:</strong> อัปโหลด (Push) Image ที่สร้างเสร็จแล้ว ไปเก็บไว้ที่ Registry Server ตามชื่อในตัวแปร</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;only:</code></td>
      <td><strong>เงื่อนไขการทำงาน:</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- main</code></td>
      <td><strong>ระบุ Branch:</strong> Job นี้จะทำงานก็ต่อเมื่อมีการ Push Code เข้ามาที่ Branch "main" เท่านั้น (Branch อื่นจะไม่ Build)</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h3>ส่วนที่ 4: Deploy Stage (Job: deploy_to_production)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th style="width: 40%">Code (คำสั่งเต็ม)</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>deploy_to_production:</code></td>
      <td><strong>ประกาศชื่อ Job:</strong> ตั้งชื่อ Job นี้ว่า "deploy_to_production"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;stage: deploy</code></td>
      <td><strong>ระบุ Stage:</strong> กำหนดให้ Job นี้อยู่ในขั้นตอน "deploy"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;image: alpine:latest</code></td>
      <td><strong>กำหนด Environment:</strong> ใช้ <code>alpine</code> Linux เวอร์ชันล่าสุด เพราะมีขนาดเล็กมาก เหมาะสำหรับงานที่ใช้แค่ SSH/SCP ไม่กินทรัพยากร</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;tags:</code></td>
      <td><strong>เริ่มการระบุ Runner:</strong> (ต้องประกาศซ้ำทุก Job)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- docker</code></td>
      <td><strong>ระบุ Tag:</strong> ส่งงานไปหา Runner ที่มี Tag "docker"</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;before_script:</code></td>
      <td><strong>คำสั่งเตรียมความพร้อม:</strong> ทำงานก่อน script หลัก (ถ้าส่วนนี้พัง script หลักจะไม่ทำ)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- apk add --no-cache openssh-client</code></td>
      <td>ติดตั้งโปรแกรม <code>ssh</code> และ <code>scp</code> (เพราะ Alpine ตัวเปล่าๆ ไม่มีมาให้)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- mkdir -p ~/.ssh</code></td>
      <td>สร้างโฟลเดอร์ <code>.ssh</code> ใน Home Directory ของ Container เพื่อเตรียมเก็บ Key</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_ed25519</code></td>
      <td>
        1. ดึงค่าจากตัวแปร <code>$SSH_PRIVATE_KEY</code> (ที่ซ่อนไว้ใน GitLab)<br />
        2. <code>tr -d '\r'</code> ลบตัวอักษรขึ้นบรรทัดใหม่แบบ Windows ทิ้ง<br />
        3. บันทึกลงไฟล์ชื่อ <code>id_ed25519</code>
      </td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- chmod 600 ~/.ssh/id_ed25519</code></td>
      <td>กำหนดสิทธิ์ไฟล์ Key ให้ <strong>"อ่าน/เขียนได้เฉพาะเจ้าของ"</strong> (ถ้าไม่ทำบรรทัดนี้ SSH จะ Error ว่า Key ไม่ปลอดภัย)</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- ssh-keyscan -H $PRODUCTION_IP >> ~/.ssh/known_hosts</code></td>
      <td>สแกนลายนิ้วมือ (Fingerprint) ของ Server ปลายทาง แล้วบันทึกไว้ เพื่อไม่ให้ SSH ถามยืนยัน (yes/no) ตอนเชื่อมต่อ</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;script:</code></td>
      <td><strong>เริ่มชุดคำสั่งหลัก:</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- scp -i ~/.ssh/id_ed25519 -r ./docker-compose.yaml $PRODUCTION_USER@$PRODUCTION_IP:/opt/docker-compose.yaml</code></td>
      <td>
        <strong>Secure Copy:</strong><br />
        - <code>-i ...</code>: ใช้ Key ไฟล์นี้ในการยืนยันตัวตน<br />
        - <code>-r</code>: (เผื่อไว้) Copy แบบ Recursive<br />
        - ส่งไฟล์ <code>docker-compose.yaml</code> จากโปรเจกต์ ไปไว้ที่ <code>/opt/</code> ของเครื่อง Production
      </td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- |</code></td>
      <td><strong>เริ่ม Block คำสั่งหลายบรรทัด:</strong> บอก YAML ว่าบรรทัดต่อจากนี้คือคำสั่งเดียวกันที่ยาวต่อเนื่อง</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ssh -i ~/.ssh/id_ed25519 $PRODUCTION_USER@$PRODUCTION_IP "</code></td>
      <td>เริ่มการเชื่อมต่อ SSH ไปยังเครื่อง Production และเปิดเครื่องหมายคำพูด <code>"</code> เพื่อเริ่มชุดคำสั่งที่จะรันบนเครื่องปลายทาง</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;cd /opt &&</code></td>
      <td>เข้าไปที่โฟลเดอร์ <code>/opt</code> (ที่วางไฟล์ไปเมื่อกี้) <strong>และ (&&)</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;docker compose pull &&</code></td>
      <td>สั่ง Docker Compose ให้ดึง (Pull) Image เวอร์ชันล่าสุดมาจาก Registry <strong>และ (&&)</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;docker compose --profile production up -d --remove-orphans</code></td>
      <td>
        1. <code>--profile production</code>: รันเฉพาะ Service ที่ระบุ profile production<br />
        2. <code>up -d</code>: เริ่มต้น Container ใหม่แบบ Background<br />
        3. <code>--remove-orphans</code>: ลบ Container เก่าที่ไม่อยู่ใน Config แล้วทิ้งไป
      </td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"</code></td>
      <td>ปิดเครื่องหมายคำพูด จบชุดคำสั่ง SSH</td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;only:</code></td>
      <td><strong>เงื่อนไขการทำงาน:</strong></td>
    </tr>
    <tr>
      <td><code>&nbsp;&nbsp;&nbsp;&nbsp;- main</code></td>
      <td><strong>ระบุ Branch:</strong> Job นี้จะทำงานก็ต่อเมื่อมีการ Push Code เข้ามาที่ Branch "main" เท่านั้น (เพื่อป้องกันการ Deploy โค้ดที่ยังไม่เสร็จ)</td>
    </tr>
  </tbody>
</table>
<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: เริ่มต้นใช้งาน (Execution)</h2>

<h3>4.1 Push Code ขึ้น GitLab</h3>
<pre><code># ในโฟลเดอร์โปรเจกต์
git init
git remote add origin https://gitlab.vecskill.ovec/root/vecskill.git
git add .
git commit -m "Setup CI/CD"
git branch -M main
git push -uf origin main</code></pre>

<h3>4.2 ตรวจสอบผลลัพธ์</h3>
<ol>
  <li>ไปที่ GitLab Project -> <strong>Build</strong> -> <strong>Pipelines</strong></li>
  <li>คุณควรจะเห็นสถานะเป็น <span style="color: green; font-weight: bold">Passed</span> ทั้ง 3 ขั้นตอน (Test, Build, Deploy)</li>
  <li>
    ไปที่เครื่อง Production ลองตรวจสอบ Container:
    <pre><code>docker ps</code></pre>
    (ควรเห็น Container <code>myservice</code>, <code>mysql2</code>, <code>phpmyadmin2</code> ทำงานอยู่)
  </li>
</ol>
