<h1>การทำระบบ CI/CD ด้วย GitLab (ตัวอย่างจริง: โปรเจกต์ PES — Node.js API + Nuxt Frontend + MySQL)</h1>
<p>
  คู่มือนี้จะพาคุณสร้าง Pipeline เพื่อ Automate กระบวนการพัฒนาซอฟต์แวร์ ตั้งแต่การทดสอบ (Test), การสร้าง Docker Image (Build), และการนำขึ้น Server จริง (Deploy) โดยอัตโนมัติเมื่อมีการ Push โค้ด
  โดยใช้ <strong>โปรเจกต์ตัวอย่างจริง PES</strong> (ระบบประเมินบุคลากร — Backend เป็น Node.js/Express, Frontend เป็น Nuxt 3, ฐานข้อมูล MySQL) ที่อยู่ภายใต้ Group ชื่อ <code>devops</code> บน GitLab
  (เช่น <code>https://gitlab.vecskill.ovec/devops/pes.git</code>) เป็นตัวอย่างประกอบตลอดทั้งหน้า
</p>
<div class="image-container">
  <div class="image-container">
    <img src="./images/CICD-01.jpg" alt="CI/CD" class="content-image" />
    <p>ภาพรวมการทำงาน: GitLab Runner ทดสอบโค้ด -> Build Image -> Push to Registry -> SSH to Production -> Pull & Run</p>
  </div>
</div>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>โครงสร้าง 2 เครื่องที่ใช้ตลอดหน้านี้:</strong> <strong>Server เครื่องที่ 1</strong> รัน GitLab + Registry + Runner (IP ภายใน <code>10.20.30.101</code> ตามบท
  <a href="#" onclick="document.querySelector('a[data-page=\'vmware-quickstart\']').click();">Quick Start (VMWare)</a>) และ <strong>Server เครื่องที่ 2</strong> ทำหน้าที่เป็น Production ที่ Runner จะ SSH
  เข้าไป Deploy (ตัวอย่างใช้ IP <code>192.168.100.102</code>) — ถ้าคุณใช้เครื่องเดียวทำทุกอย่าง ให้แทน IP ของ Production ด้วย IP เดียวกับ Server เครื่องที่ 1 ได้เลย
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 0: สร้าง Personal Access Token (สำหรับ Clone/Push ผ่าน HTTPS)</h2>
<p>
  GitLab ไม่อนุญาตให้ใช้รหัสผ่านบัญชีปกติตอน Clone/Push ผ่าน HTTPS โดยตรง (เพื่อความปลอดภัย) นักพัฒนาแต่ละคนในทีมจึงต้องสร้าง <strong>Personal Access Token</strong> ของตัวเองมาใช้แทนรหัสผ่านก่อน
  (ใช้ครั้งเดียว โปรแกรมจัดการ Git เช่น Git Credential Manager หรือ GitHub Desktop จะจำให้หลังจากนั้น)
</p>
<ol>
  <li>ล็อกอิน <code>https://gitlab.vecskill.ovec</code> แล้วคลิกรูปโปรไฟล์มุมขวาบน &gt; <strong>Edit profile</strong></li>
  <li>ไปที่เมนู <strong>Access Tokens</strong> ทางซ้าย</li>
  <li>
    กรอกข้อมูล:
    <ul>
      <li><strong>Token name:</strong> ตั้งชื่อ เช่น <code>pes-dev-laptop</code></li>
      <li><strong>Expiration date:</strong> กำหนดวันหมดอายุ (หรือปล่อยว่างถ้า GitLab เวอร์ชันนี้อนุญาต)</li>
      <li><strong>Scopes:</strong> ติ๊ก <code>read_repository</code> และ <code>write_repository</code> (แค่ 2 อันนี้พอสำหรับ Clone/Push โค้ด ไม่ต้องติ๊กอย่างอื่น)</li>
    </ul>
  </li>
  <li>กด <strong>Create personal access token</strong> แล้ว <strong>คัดลอกค่าที่ขึ้นต้นด้วย <code>glpat-</code> เก็บไว้ทันที</strong> (หน้านี้จะแสดงให้เห็นครั้งเดียวเท่านั้น)</li>
</ol>
<p>นำ Token นี้ไปใช้แทนรหัสผ่านตอน Clone หรือ Push ครั้งแรก:</p>
<pre><code class="language-bash">git clone https://gitlab.vecskill.ovec/devops/pes.git
Username: &lt;ชื่อ user ของคุณ&gt;
Password: &lt;วาง Token glpat-... ตรงนี้&gt;</code></pre>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>หมายเหตุ:</strong> Token นี้ใช้เฉพาะตอนนักพัฒนา Clone/Push โค้ดด้วยมือเองเท่านั้น <strong>ไม่เกี่ยวกับ Pipeline ของ GitLab CI/CD</strong> — ตอน Runner รันงานจริง GitLab จะออก
  <code>$CI_JOB_TOKEN</code> ให้อัตโนมัติทุกครั้งโดยไม่ต้องตั้งค่าอะไรเพิ่ม
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: เตรียมความพร้อม (Infrastructure)</h2>

<h3>1.1 เลือกวิธีให้ Runner Push/Pull ผ่าน Registry: Insecure หรือ HTTPS</h3>
<p>
  เราใช้ Registry ภายใน (<code>registry.vecskill.ovec</code> ตามบท <a href="#" onclick="document.querySelector('a[data-page=\'registry\']').click();">การติดตั้ง Registry & UI</a>) เลือกได้ 2 แบบ
  ขึ้นอยู่กับว่าคุณตั้งค่า <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">Caddy Internal CA</a> ไว้แล้วหรือยัง — ทั้งสองแบบใช้ไฟล์ <code>.gitlab-ci.yml</code>
  เกือบเหมือนกันทุกตัวอักษร ต่างกันแค่ 2 จุดที่จะชี้ให้ดูด้านล่าง
</p>

<h4 style="color: #c0392b">แบบที่ 1: ไม่ใช้ SSL (Insecure Registry — ตั้งค่าเร็ว เหมาะกับทดสอบ/Lab)</h4>
<p>
  ขั้นตอนการ Build ของ Pipeline นี้ใช้เทคนิค <strong>Docker-in-Docker (dind)</strong> คือ Runner จะสร้าง Docker Daemon ใหม่ชั่วคราวขึ้นมาทุกครั้งที่รัน Job (ไม่ใช่ Daemon เดียวกับเครื่อง Host)
  Daemon ชั่วคราวนี้<strong>ไม่ได้อ่านไฟล์ <code>/etc/docker/daemon.json</code> ของเครื่อง Host เลย</strong> ดังนั้นการตั้งค่า Insecure Registry สำหรับฝั่ง Build
  ต้องส่ง Flag <code>--insecure-registry</code> ตรงเข้าไปที่ตัว dind Service โดยตรงในไฟล์ <code>.gitlab-ci.yml</code> (แสดงในส่วนที่ 3.6 ด้านล่าง) <strong>ไม่ใช่</strong>แก้ที่ <code>daemon.json</code>
  ของเครื่อง Host แต่อย่างใด
</p>
<p>
  ส่วนฝั่ง Production (ตอน <code>docker compose pull</code>) ยังคงต้องตั้งค่า <code>/etc/docker/daemon.json</code> ตามปกติ เพราะ Docker Compose บนเครื่อง Production รันผ่าน Daemon จริงของเครื่องนั้น
  (ไม่ใช่ dind):
</p>
<pre><code class="language-json">{
  "insecure-registries": ["registry.vecskill.ovec:5000"]
}</code></pre>
<pre><code>sudo systemctl restart docker</code></pre>
<p>ในแบบนี้ ชื่อ Image ทุกที่จะมี <code>:5000</code> ต่อท้ายโดเมนเสมอ เช่น <code>registry.vecskill.ovec:5000/pes-api:latest</code></p>

<h4 style="color: #28a745">แบบที่ 2: ใช้ SSL ได้ (HTTPS ผ่าน Caddy Internal CA — แนะนำ ปลอดภัยกว่า)</h4>
<p>
  ถ้าทำตามบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ไว้แล้ว คุณจะเรียก <code>https://registry.vecskill.ovec</code> และ
  <code>https://registry-ui.vecskill.ovec</code> ได้โดยไม่มีคำเตือนจากเบราว์เซอร์ แต่สำหรับขั้นตอน <strong>Build ใน dind ยังคงแนะนำให้ใช้ Flag
  <code>--insecure-registry</code> เหมือนแบบที่ 1</strong> เพราะ dind เป็น Container ชั่วคราวที่ถูกสร้างและทำลายทุก Job การจะติดตั้ง Root CA ของ Caddy ให้ Container ชั่วคราวนี้เชื่อถือทุกครั้งทำได้ยาก
  และไม่คุ้มค่า (ข้อมูลระหว่าง Build ยังคงถูกเข้ารหัสผ่าน TLS อยู่ดี เพียงแต่ไม่ตรวจสอบ Certificate เท่านั้น)
</p>
<p>
  จุดที่ <strong>ต้องทำ</strong> สำหรับแบบนี้คือฝั่ง <strong>Production</strong> (ตอน <code>docker compose pull</code> ซึ่งรันบน Daemon จริงของเครื่อง ไม่ใช่ dind) ให้ติดตั้ง Root CA ของ Caddy ตามขั้นตอนที่
  3.4 ในบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> (ใช้ IP <code>10.20.30.101</code> เป็น Caddy Server และ
  <code>192.168.100.102</code> เป็น Production ตามตัวอย่างในบทนั้นพอดี) แล้วเปลี่ยนชื่อ Image ในไฟล์ <code>.gitlab-ci.yml</code>/<code>docker-compose.prod.yml</code> ให้ไม่มี <code>:5000</code> ต่อท้าย
  เช่น <code>registry.vecskill.ovec/pes-api:latest</code>
</p>

<h3>1.2 เตรียม User <code>devops</code> บนเครื่อง Production</h3>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>อย่าใช้ `root` ในการ Deploy:</strong> ตามที่เราปิดการ Login ด้วย `root` ผ่าน SSH ไปแล้วในบท
  <a href="#" onclick="document.querySelector('a[data-page=\'ubuntu-setup\']').click();">การเตรียม Ubuntu Server</a> ขั้นตอน CI/CD ทั้งหมดนี้จึงต้องใช้ User ปกติ (<code>devops</code>) แทน `root`
  ด้วยเช่นกันเพื่อให้สอดคล้องกัน
</p>
<ol>
  <li>
    <strong>เพิ่มเข้ากลุ่ม <code>docker</code>:</strong> เพื่อสั่ง <code>docker</code>/<code>docker compose</code> โดยไม่ต้อง <code>sudo</code>
    <pre><code>sudo usermod -aG docker devops</code></pre>
    Logout แล้ว Login ใหม่ (หรือรัน <code>newgrp docker</code>) เพื่อให้สิทธิ์กลุ่มมีผล แล้วทดสอบว่าเรียก <code>docker ps</code> ได้โดยไม่ต้อง sudo
  </li>
  <li>
    <strong>เปิด NOPASSWD sudo:</strong> สคริปต์ Deploy ต้องสั่ง <code>sudo mkdir</code>/<code>sudo chown</code> แบบอัตโนมัติผ่าน SSH (ไม่มีคนนั่งพิมพ์รหัสผ่าน) จึง<strong>ต้อง</strong>ทำตามบท
    <a href="#" onclick="document.querySelector('a[data-page=\'ubuntu-setup\']').click();">การเตรียม Ubuntu Server</a> หัวข้อ "6. การใช้ sudo โดยไม่ต้องใส่รหัสผ่าน" ให้ User <code>devops</code> ไว้ก่อน
    ไม่เช่นนั้น Job <code>deploy_prod</code> จะค้าง (Timeout) ตรงคำสั่ง <code>sudo</code> แรกเสมอ
  </li>
</ol>

<h3>1.3 สร้าง SSH Key สำหรับ Deploy</h3>
<p>เราต้องสร้างกุญแจเพื่อให้ GitLab Runner สามารถ SSH เข้าไปวางไฟล์และรันคำสั่งที่เครื่อง Production (ในฐานะ User <code>devops</code>) ได้โดยไม่ต้องใช้รหัสผ่าน</p>
<ol>
  <li>
    <strong>ที่เครื่อง DevOps (หรือเครื่องของคุณ):</strong> สร้าง Key
    <pre><code>ssh-keygen -t ed25519 -C "gitlab-ci-deploy"</code></pre>
    (กด Enter ไปเรื่อยๆ ไม่ต้องใส่ Passphrase เพราะ Pipeline ต้องใช้งานแบบไม่มีคนโต้ตอบ)
  </li>
  <li>
    <strong>ดู Public Key (ตัวแม่กุญแจ):</strong>
    <pre><code>cat ~/.ssh/id_ed25519.pub</code></pre>
  </li>
  <li>
    <strong>นำ Public Key ไปใส่ที่เครื่อง Production (ในฐานะ User `devops`):</strong>
    <ul>
      <li>SSH เข้าไปที่เครื่อง Production ด้วย User <code>devops</code></li>
      <li>แก้ไขไฟล์ <code>~/.ssh/authorized_keys</code> (คือ <code>/home/devops/.ssh/authorized_keys</code>) วาง Public Key ต่อท้ายบรรทัดเดิม</li>
    </ul>
  </li>
  <li>
    <strong>ดู Private Key (ตัวลูกกุญแจ - เก็บเป็นความลับ):</strong>
    <pre><code>cat ~/.ssh/id_ed25519</code></pre>
    <em>คัดลอกทั้งหมดรวมบรรทัด <code>-----BEGIN OPENSSH PRIVATE KEY-----</code> และ <code>-----END OPENSSH PRIVATE KEY-----</code> เก็บไว้ใช้ในขั้นตอนถัดไป</em>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: ตั้งค่า GitLab Group/Project</h2>
<p>
  เพราะโปรเจกต์ของเราอยู่ภายใต้ <strong>Group</strong> (เช่น <code>devops</code>) ไม่ใช่ Project เดี่ยวๆ เราจะตั้งค่าตัวแปรที่ระดับ <strong>Group</strong> แทน Project เพื่อให้สมาชิกในทีมและ Project อื่นๆ
  ในอนาคตใช้ค่าชุดเดียวกันได้ทันทีโดยไม่ต้องตั้งซ้ำทีละโปรเจกต์
</p>

<h3>2.1 สร้าง Group และ Project</h3>
<ol>
  <li>หน้าแรก GitLab &gt; <strong>Create new</strong> &gt; <strong>New group</strong> ตั้งชื่อ <code>devops</code></li>
  <li>ในหน้า Group ที่สร้างเสร็จ กด <strong>Create new project</strong> &gt; <strong>Create blank project</strong> ตั้งชื่อ <code>pes</code> (จะได้ URL <code>https://gitlab.vecskill.ovec/devops/pes.git</code>)</li>
</ol>

<h3>2.2 ตั้งค่า Group CI/CD Variables</h3>
<p>ไปที่หน้า <strong>Group</strong> <code>devops</code> &gt; <strong>Settings</strong> &gt; <strong>CI/CD</strong> &gt; <strong>Variables</strong> &gt; <strong>Add variable</strong> เพิ่ม 2 ตัวนี้:</p>
<table class="spec-table">
  <thead>
    <tr>
      <th>Key</th>
      <th>Value</th>
      <th>Flags ที่ต้องตั้ง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>CI_REGISTRY</code></td>
      <td>
        <code>registry.vecskill.ovec:5000</code> (แบบ Insecure) หรือ <code>registry.vecskill.ovec</code> (แบบ HTTPS — ไม่มี Port)
      </td>
      <td>ไม่ต้องติ๊ก Protect/Masked (ไม่ใช่ความลับ)</td>
    </tr>
    <tr>
      <td><code>DEPLOY_PRIV_KEY</code></td>
      <td>วาง Private Key ทั้งหมดจากขั้นตอนที่ 1.3 (รวมบรรทัด BEGIN/END)</td>
      <td>
        ติ๊ก <strong>Protect variable</strong> เท่านั้น (จำกัดให้ใช้ได้เฉพาะ Branch <code>main</code> ที่ตั้งเป็น Protected Branch)<br />
        <strong style="color: #c0392b">ห้ามติ๊ก Masked</strong> — GitLab จะปฏิเสธทันทีเพราะ Private Key มีการขึ้นบรรทัดใหม่/ช่องว่างซึ่ง Masked Variable ไม่รองรับ
      </td>
    </tr>
  </tbody>
</table>
<p style="font-size: 0.9em; color: #666">
  หมายเหตุ: ชื่อ <code>CI_REGISTRY</code> ไม่ชนกับตัวแปรสำรองของ GitLab เอง เพราะคู่มือนี้ใช้ Registry แยกต่างหาก (ไม่ได้เปิดใช้ GitLab Built-in Container Registry) ตัวแปรนี้จึงว่างเปล่าโดยปริยาย
  และนำมากำหนดใช้เองได้อย่างปลอดภัย
</p>

<h3>2.3 สร้าง Runner</h3>
<p>
  ถ้ายังไม่เคยติดตั้ง GitLab Runner มาก่อน ให้ทำตามบท <a href="#" onclick="document.querySelector('a[data-page=\'runner\']').click();">การติดตั้ง Gitlab Runner</a> ให้ครบก่อน (สำคัญมาก: ต้องตั้ง
  <code>network_mode = "app_net"</code> และต้องเพิ่ม Tag <code>docker</code> ให้ Runner ผ่านหน้าเว็บ ไม่เช่นนั้น Job ในบทนี้จะค้างสถานะ Pending)
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: เตรียมไฟล์โปรเจกต์ (จากโปรเจกต์ PES จริง)</h2>

<h3>3.1 โครงสร้างโฟลเดอร์</h3>
<pre><code>pes/
├── .gitlab-ci.yml            # ไฟล์ควบคุม Pipeline (Test → Build → Deploy)
├── .gitignore
├── docker-compose.yml        # รันพัฒนาบนเครื่อง Local (Build จากซอร์สโค้ดตรงๆ)
├── docker-compose_mysql.yml  # รันเฉพาะฐานข้อมูล MySQL+phpMyAdmin เผื่ออยาก Dev เร็วๆ โดยไม่ build API/Frontend
├── docker-compose.prod.yml   # ใช้ตอน Deploy บน Production (ดึง Image สำเร็จรูปจาก Registry มารัน ไม่ Build ที่เครื่องนี้)
├── schema.sql                # สคริปต์สร้างตารางฐานข้อมูล (ใช้กับ docker-compose.yml บน Local)
├── backend/                  # ซอร์สโค้ด API (Node.js/Express) + Dockerfile
├── frontend/                 # ซอร์สโค้ดเว็บ (Nuxt 3) + Dockerfile
└── deploy/                   # ไฟล์ที่ Pipeline จะ rsync ไปเครื่อง Production เท่านั้น (ไม่เอาทั้ง Repo ไป)
    ├── schema.sql             # สำเนาของ schema.sql ไว้ให้ MySQL บน Production รันตอนเริ่มครั้งแรก
    └── .env                   # ค่า Environment ของ API บน Production (DB_HOST=db เหมือนกัน)</code></pre>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ทำไมต้องมีโฟลเดอร์ <code>deploy/</code> แยกต่างหาก:</strong> Pipeline ไม่ได้ Clone ทั้ง Repo ไปไว้ที่ Production (ไม่จำเป็น เพราะ Production แค่รัน Image สำเร็จรูป ไม่ต้องมีซอร์สโค้ด)
  จึงส่งไปเฉพาะไฟล์ที่จำเป็นจริงๆ คือ <code>docker-compose.prod.yml</code> กับโฟลเดอร์ <code>deploy/</code> นี้เท่านั้น (ผ่านคำสั่ง <code>rsync</code> ในขั้นตอน Deploy)
</p>

<h3>3.2 Dockerfile (จากโปรเจกต์ PES จริง — ไม่ต้องแก้ไข)</h3>
<p><strong><code>backend/Dockerfile</code></strong> (Express API, Listen Port 7000):</p>
<pre><code class="language-dockerfile">FROM node:20-alpine
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
EXPOSE 7000
CMD ["npm","start"]</code></pre>
<p><strong><code>frontend/Dockerfile</code></strong> (Nuxt 3, Listen Port 3000):</p>
<pre><code class="language-dockerfile">FROM node:20-alpine
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
EXPOSE 3000
CMD ["npm","run","dev"]</code></pre>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: Dockerfile ของ Frontend รัน <code>npm run dev</code> (โหมด Dev Server ของ Nuxt) แม้บน Production ก็ตาม ซึ่งใช้งานได้จริงตามที่ทดสอบมา แต่ถ้าต้องการประสิทธิภาพที่ดีขึ้นในอนาคต
  สามารถปรับเป็น Multi-stage Build ที่สั่ง <code>npm run build</code> แล้วใช้ <code>npm start</code> (ซึ่งมี Script นี้เตรียมไว้ใน <code>package.json</code> แล้ว) แทนได้ ไม่กระทบกับคู่มือส่วนที่เหลือ</em>
</p>

<h3>3.3 docker-compose.yml (สำหรับเครื่อง Local — Build จากซอร์สโค้ด)</h3>
<pre><code class="language-yaml">services:
  db:
    image: mysql:8.0
    command: >
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: skills_db
      TZ: Asia/Bangkok
    # ไม่เปิด 3306 ออก host กันชนกับ MySQL ที่อาจติดตั้งไว้บนเครื่องคุณแล้ว ถ้าจำเป็นค่อยเปิด "3307:3306"
    volumes:
      - db_data:/var/lib/mysql
      - ./schema.sql:/docker-entrypoint-initdb.d/00-schema.sql:ro
    healthcheck:
      test: ["CMD","mysqladmin","ping","-h","127.0.0.1","-uroot","-prootpassword"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks: [dev_net]

  phpmyadmin:
    image: phpmyadmin:latest
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: rootpassword
    ports: ["8080:80"]
    depends_on: [db]
    networks: [dev_net]

  api:
    build: ./backend
    env_file:
      - ./backend/.env
    ports: ["7000:7000"]
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - ./backend/uploads:/app/uploads
    networks: [dev_net]

  frontend:
    build: ./frontend
    environment:
      NUXT_PUBLIC_API_BASE: http://localhost:7000
      NITRO_PORT: 3000
      HOST: 0.0.0.0
    ports: ["3000:3000"]
    depends_on: [api]
    networks: [dev_net]

volumes:
  db_data:

networks:
  dev_net:
    driver: bridge</code></pre>
<p>
  <strong><code>docker-compose_mysql.yml</code></strong> (ทางเลือกเสริม — เปิดเฉพาะฐานข้อมูล+phpMyAdmin ตอนอยากรัน API/Frontend แบบ <code>npm run dev</code> ตรงๆ บนเครื่องโดยไม่ต้อง Build Docker Image ทุกครั้ง)
  มีโครงเหมือน <code>db</code>/<code>phpmyadmin</code> ด้านบน แค่เปิดพอร์ต <code>3306</code> ออกสู่ Host เพิ่ม เพื่อให้ API ที่รันตรงบนเครื่อง (ไม่ใช่ใน Container) เชื่อมต่อ
  <code>localhost:3306</code> ได้
</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>จุดที่มักพิมพ์ผิด:</strong> ถ้าคัดลอกไฟล์นี้มาจากที่อื่น ให้เช็ค <code>healthcheck</code> ให้ใช้รหัสผ่านตรงกับ <code>MYSQL_ROOT_PASSWORD</code> เป๊ะๆ เช่น
  <code>-prootpassword</code> (เกาะติดกัน ไม่มีขีดล่าง) ไม่ใช่ <code>-proot_password</code> ไม่งั้น Container จะค้างสถานะ "unhealthy" ตลอดเวลา เพราะ <code>mysqladmin ping</code> ล็อกอินด้วยรหัสผ่านที่ผิดไม่ผ่าน
</p>

<h3>3.4 docker-compose.prod.yml (ใหม่ — สำหรับ Production เท่านั้น ดึง Image สำเร็จรูปมารัน)</h3>
<p>
  ไฟล์นี้<strong>ไม่มีอยู่</strong>ในโปรเจกต์ตั้งต้น ต้องสร้างขึ้นมาใหม่ที่ Root ของ Repo ความแตกต่างหลักจาก <code>docker-compose.yml</code> คือใช้ <code>image:</code> (ดึง Image ที่ Build เสร็จแล้วจาก Registry)
  แทน <code>build:</code> (สร้าง Image จากซอร์สโค้ด) และอ้างอิงไฟล์ใต้โฟลเดอร์ <code>./deploy/</code> แทนที่จะอยู่ Root ตรงๆ:
</p>
<pre><code class="language-yaml">services:
  db:
    image: mysql:8.0
    restart: unless-stopped
    command: >
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: skills_db
      TZ: Asia/Bangkok
    volumes:
      - db_data:/var/lib/mysql
      - ./deploy/schema.sql:/docker-entrypoint-initdb.d/00-schema.sql:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-prootpassword"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks: [prod_net]

  phpmyadmin:
    image: phpmyadmin:latest
    restart: unless-stopped
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: rootpassword
    ports: ["8080:80"]
    depends_on:
      db:
        condition: service_healthy
    networks: [prod_net]

  api:
    image: ${CI_REGISTRY}/pes-api:${IMAGE_TAG:-latest}
    restart: unless-stopped
    env_file:
      - ./deploy/.env
    ports: ["7000:7000"]
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - ./uploads:/app/uploads
    networks: [prod_net]

  frontend:
    image: ${CI_REGISTRY}/pes-frontend:${IMAGE_TAG:-latest}
    restart: unless-stopped
    environment:
      NUXT_PUBLIC_API_BASE: ${NUXT_PUBLIC_API_BASE:-http://192.168.100.102:7000}
      NITRO_PORT: 3000
      HOST: 0.0.0.0
    ports: ["3000:3000"]
    depends_on: [api]
    networks: [prod_net]

volumes:
  db_data:

networks:
  prod_net:
    driver: bridge</code></pre>
<p>
  <code>deploy/.env</code> คือสำเนา <code>backend/.env</code> ปรับให้เหมาะกับ Production (เช่น <code>CORS_ORIGIN</code> ให้ชี้มาที่ Domain จริงของ Frontend แทน <code>localhost</code>) ส่วน
  <code>deploy/schema.sql</code> คือไฟล์เดียวกับ <code>schema.sql</code> ที่ Root ของ Repo คัดลอกมาวางไว้
</p>

<h3>3.5 .gitlab-ci.yml (ไฟล์ Pipeline ฉบับสมบูรณ์)</h3>
<p>วางไฟล์นี้ไว้ที่ Root ของ Repo (ตัวอย่างนี้ใช้แบบ <strong>Insecure Registry</strong> — ดูหมายเหตุท้ายโค้ดสำหรับการสลับไปใช้ HTTPS):</p>
<pre><code class="language-yaml">stages: [test, build, deploy]

# ===== Global variables =====
variables:
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: "/certs"
  IMAGE_TAG: "${CI_COMMIT_SHORT_SHA}"
  API_IMAGE: "${CI_REGISTRY}/pes-api:${IMAGE_TAG}"
  FRONTEND_IMAGE: "${CI_REGISTRY}/pes-frontend:${IMAGE_TAG}"

  # === Deploy target (Production) ===
  DEPLOY_HOST: "192.168.100.102"
  DEPLOY_PORT: "22"
  DEPLOY_USER: "devops"
  DEPLOY_PATH: "/opt/pes"

default:
  tags: [docker]
  cache:
    key: ${CI_COMMIT_REF_SLUG}
    paths:
      - frontend/node_modules
      - backend/node_modules

# ===== TEST =====
test_backend:
  stage: test
  image: node:20-alpine
  script:
    - cd backend
    - npm ci
    - npm run lint --if-present
    - npm test --if-present
  rules:
    - changes: ["backend/**"]
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"

test_frontend:
  stage: test
  image: node:20-alpine
  script:
    - cd frontend
    - npm ci
    - npm run lint --if-present
    - npm test --if-present
  rules:
    - changes: ["frontend/**"]
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"

# ===== BUILD & PUSH (Docker-in-Docker) =====
.build_template:
  image: docker:26
  services:
    - name: docker:26-dind
      command: ["--insecure-registry=registry.vecskill.ovec:5000"] # ลบบรรทัดนี้ทิ้งถ้าใช้ HTTPS (ดูหมายเหตุด้านล่าง)
  before_script:
    - |
      if [ -n "$CI_REGISTRY_USER" ] && [ -n "$CI_REGISTRY_PASSWORD" ]; then
        echo "$CI_REGISTRY_PASSWORD" | docker login -u "$CI_REGISTRY_USER" "$CI_REGISTRY" --password-stdin
      fi

build_api:
  stage: build
  extends: .build_template
  needs: ["test_backend"]
  script:
    - docker build -t "$API_IMAGE" ./backend
    - docker push "$API_IMAGE"
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then
        docker tag "$API_IMAGE" "${CI_REGISTRY}/pes-api:latest"
        docker push "${CI_REGISTRY}/pes-api:latest"
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["backend/**"]

build_frontend:
  stage: build
  extends: .build_template
  needs: ["test_frontend"]
  script:
    - docker build -t "$FRONTEND_IMAGE" ./frontend
    - docker push "$FRONTEND_IMAGE"
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then
        docker tag "$FRONTEND_IMAGE" "${CI_REGISTRY}/pes-frontend:latest"
        docker push "${CI_REGISTRY}/pes-frontend:latest"
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["frontend/**"]

# ===== DEPLOY (SSH to Production with docker compose) =====
deploy_prod:
  stage: deploy
  image: alpine:3.20
  needs:
    - job: build_api
      optional: true
    - job: build_frontend
      optional: true
  environment:
    name: production
    url: http://$DEPLOY_HOST:3000
    on_stop: stop_prod
  before_script:
    - apk add --no-cache openssh-client rsync bash
    - mkdir -p ~/.ssh && chmod 700 ~/.ssh
    - echo "$DEPLOY_PRIV_KEY" > ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" >> ~/.ssh/known_hosts || true
    - export REMOTE_DIR="${DEPLOY_PATH}"
  script:
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "sudo mkdir -p $REMOTE_DIR/deploy && sudo chown -R \$USER:\$USER $REMOTE_DIR"
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" docker-compose.prod.yml "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/"
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" deploy/ "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/deploy/"
    - |
      if [ -n "$CI_REGISTRY_USER" ] && [ -n "$CI_REGISTRY_PASSWORD" ]; then
        ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "echo \"$CI_REGISTRY_PASSWORD\" | docker login -u \"$CI_REGISTRY_USER\" \"$CI_REGISTRY\" --password-stdin"
      fi
    - >
      ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}"
      "cd $REMOTE_DIR &&
       IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000'
       docker compose -f docker-compose.prod.yml pull &&
       IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000'
       docker compose -f docker-compose.prod.yml up -d --remove-orphans"
  rules:
    # Deploy เฉพาะ Branch main และเป็น Manual (กันพลาด) — ไปกดปุ่ม Play เองที่หน้า Pipelines
    - if: '$CI_COMMIT_BRANCH == "main"'
      when: manual
      allow_failure: false

# ===== STOP Production (ทางเลือก) =====
stop_prod:
  stage: deploy
  image: alpine:3.20
  environment:
    name: production
    action: stop
  before_script:
    - apk add --no-cache openssh-client
    - mkdir -p ~/.ssh && chmod 700 ~/.ssh
    - echo "$DEPLOY_PRIV_KEY" > ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" >> ~/.ssh/known_hosts || true
  script:
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd ${DEPLOY_PATH} && docker compose -f docker-compose.prod.yml down"
  rules:
    - when: manual
      allow_failure: true</code></pre>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>สลับไปใช้ HTTPS แทน Insecure:</strong> แก้แค่ 2 จุด — (1) ลบบรรทัด <code>command: ["--insecure-registry=..."]</code> ออกจาก <code>.build_template</code> ทั้งหมด
  (2) เปลี่ยนค่า Group Variable <code>CI_REGISTRY</code> จาก <code>registry.vecskill.ovec:5000</code> เป็น <code>registry.vecskill.ovec</code> (ไม่มี Port) — ที่เหลือไม่ต้องแก้อะไรเลย
  เพราะชื่อ Image ทุกจุดอ้างอิงผ่านตัวแปร <code>$CI_REGISTRY</code> อยู่แล้ว (อย่าลืมติดตั้ง Root CA บน Production ตามที่อธิบายในข้อ 1.1 แบบที่ 2 ด้วย)
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: เจาะลึกการทำงานของ .gitlab-ci.yml ทีละส่วน</h2>

<h3>4.1 ตัวแปร Global</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ตัวแปร</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>IMAGE_TAG: "${CI_COMMIT_SHORT_SHA}"</code></td>
      <td>ใช้ Hash ของ Commit (ย่อ) เป็น Tag ของ Image แต่ละรอบ Build แทนการใช้ <code>latest</code> ตลอด เพื่อให้ Image แต่ละเวอร์ชันแยกจากกันชัดเจน ย้อนดู/ย้อนกลับได้</td>
    </tr>
    <tr>
      <td><code>API_IMAGE</code> / <code>FRONTEND_IMAGE</code></td>
      <td>ประกอบชื่อ Image เต็มจาก <code>$CI_REGISTRY</code> (Group Variable ที่ตั้งไว้) + ชื่อ Image แบบชั้นเดียว (<code>pes-api</code>, <code>pes-frontend</code>) + Tag — ใช้ชื่อแบบชั้นเดียวเพื่อไม่ให้สับสนกับ
      Path ของ Registry UI</td>
    </tr>
    <tr>
      <td><code>DEPLOY_HOST</code> / <code>DEPLOY_USER</code> / <code>DEPLOY_PATH</code></td>
      <td>ข้อมูลเครื่อง Production ที่จะ SSH เข้าไป Deploy — เปลี่ยนตามเครื่องจริงของคุณ</td>
    </tr>
  </tbody>
</table>

<h3>4.2 Stage: test_backend / test_frontend</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ส่วนของโค้ด</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>image: node:20-alpine</code></td>
      <td>ใช้ Image Node.js 20 เวอร์ชันเล็ก (Alpine) มาจำลองสภาพแวดล้อมสำหรับรัน <code>npm</code></td>
    </tr>
    <tr>
      <td><code>rules: changes: ["backend/**"]</code></td>
      <td><strong>จุดสำคัญ:</strong> Job นี้จะรันก็ต่อเมื่อมีไฟล์ในโฟลเดอร์ <code>backend/</code> เปลี่ยนแปลงเท่านั้น ถ้าคุณแก้แค่ <code>frontend/</code> Job <code>test_backend</code> จะไม่รันเลย ช่วยประหยัดเวลา
      Pipeline โดยรวม</td>
    </tr>
    <tr>
      <td><code>if: $CI_PIPELINE_SOURCE == "merge_request_event"</code></td>
      <td>ให้รัน Test ทุกครั้งที่เปิด Merge Request ด้วย (ไม่ว่าจะแก้ไฟล์โฟลเดอร์ไหนก็ตาม) เพื่อตรวจสอบก่อน Merge เข้า <code>main</code></td>
    </tr>
    <tr>
      <td><code>npm run lint --if-present</code> / <code>npm test --if-present</code></td>
      <td>Flag <code>--if-present</code> ทำให้คำสั่งนี้ "ข้ามแบบไม่ Error" ถ้าโปรเจกต์ไม่มี Script ชื่อ <code>lint</code>/<code>test</code> ใน <code>package.json</code> (กรณีของ Frontend ในโปรเจกต์ PES ยังไม่มี Script
      <code>lint</code> ตอนนี้ จึงจะถูกข้ามไปโดยไม่ทำให้ Pipeline แดง)</td>
    </tr>
  </tbody>
</table>

<h3>4.3 Stage: build_api / build_frontend (Docker-in-Docker)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ส่วนของโค้ด</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>services: - name: docker:26-dind<br />&nbsp;&nbsp;command: ["--insecure-registry=..."]</code></td>
      <td>เปิด Docker Daemon ชั่วคราวขึ้นมาให้ Job นี้ พร้อมสั่งให้ Daemon ชั่วคราวนั้นยอมรับ Registry ที่ไม่มี Certificate ถูกต้อง (จำเป็นสำหรับแบบ Insecure เท่านั้น — ลบทิ้งถ้าใช้ HTTPS)</td>
    </tr>
    <tr>
      <td><code>if [ -n "$CI_REGISTRY_USER" ] ...</code></td>
      <td>ตรวจก่อนว่ามีการตั้งค่า Username/Password ของ Registry ไว้หรือไม่ (รองรับทั้งกรณี Registry เปิด Authentication และกรณีปิดไว้ ไม่ Error ถ้าไม่ได้ตั้งค่าไว้)</td>
    </tr>
    <tr>
      <td><code>needs: ["test_backend"]</code></td>
      <td>บังคับให้ Job นี้รอ <code>test_backend</code> ผ่านก่อนเสมอ (Build ไม่สำเร็จถ้า Test ไม่ผ่าน)</td>
    </tr>
    <tr>
      <td>Tag <code>:latest</code> เฉพาะ Branch <code>main</code></td>
      <td>Nontag ชั่วคราว (ตาม Commit) ถูก Push ทุกครั้ง แต่ Tag <code>latest</code> (ที่ฝั่ง Deploy จะไปดึงมาใช้) จะถูกอัปเดตเฉพาะตอน Push เข้า <code>main</code> เท่านั้น กัน Branch ทดลองไปทับ Production
      โดยไม่ได้ตั้งใจ</td>
    </tr>
  </tbody>
</table>

<h3>4.4 Stage: deploy_prod (SSH + rsync)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ส่วนของโค้ด</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>needs: - job: build_api<br />&nbsp;&nbsp;optional: true</code></td>
      <td><strong>จุดสำคัญ:</strong> ใช้ <code>optional: true</code> แทนการบังคับทั้งคู่แบบเดิม เพราะถ้า Commit นั้นแก้แค่ <code>frontend/</code> (ตาม <code>rules: changes</code> ในข้อ 4.3) Job
      <code>build_api</code> จะไม่ถูกสร้างขึ้นมาเลยในรอบนั้น ถ้าใช้ <code>needs</code> แบบบังคับ Pipeline จะ Error ทันทีว่าหา Job ที่ต้องรอไม่เจอ</td>
    </tr>
    <tr>
      <td><code>environment: name: production ... on_stop: stop_prod</code></td>
      <td>ลงทะเบียน Environment ชื่อ "production" ไว้ในเมนู Deployments ของ GitLab และผูกกับ Job <code>stop_prod</code> สำหรับกดหยุดระบบทีหลังได้จากหน้าเว็บ</td>
    </tr>
    <tr>
      <td><code>ssh-keyscan -p "$DEPLOY_PORT" ... >> known_hosts</code></td>
      <td>บันทึก Fingerprint ของเครื่อง Production ไว้ล่วงหน้า กัน SSH ถามยืนยัน (yes/no) ซึ่งจะทำให้ Pipeline ค้าง เพราะไม่มีคนนั่งพิมพ์ตอบ</td>
    </tr>
    <tr>
      <td><code>sudo mkdir -p $REMOTE_DIR/deploy && sudo chown ...</code></td>
      <td>สร้างโฟลเดอร์ปลายทางอัตโนมัติ (ต้องมี NOPASSWD sudo ตามข้อ 1.2 ไม่งั้นค้างตรงนี้ทันที) แล้วเปลี่ยนเจ้าของเป็น User <code>devops</code> เอง เพื่อให้ rsync ครั้งถัดไปเขียนไฟล์ได้โดยไม่ต้อง sudo</td>
    </tr>
    <tr>
      <td><code>rsync ... docker-compose.prod.yml ...</code><br /><code>rsync ... deploy/ ...</code></td>
      <td>ส่งเฉพาะไฟล์ที่จำเป็นสำหรับรัน Production ไปที่เครื่องปลายทาง (ไม่ส่งซอร์สโค้ด <code>backend/</code>/<code>frontend/</code> ไปด้วย เพราะ Production ใช้ Image สำเร็จรูปจาก Registry อยู่แล้ว)</td>
    </tr>
    <tr>
      <td><code>IMAGE_TAG='latest' ... docker compose -f docker-compose.prod.yml pull</code></td>
      <td>สั่งให้ Production ดึง Image Tag <code>latest</code> เสมอ (ไม่ใช่ Tag ตาม Commit Hash ของรอบนี้) เพื่อให้ตรงกับ Tag ที่ Job Build อัปเดตไว้ในข้อ 4.3 โดยไม่ต้องส่งค่า Commit Hash ข้ามมาให้ตรงกันเป๊ะ</td>
    </tr>
    <tr>
      <td><code>rules: if: $CI_COMMIT_BRANCH == "main"<br />&nbsp;&nbsp;when: manual</code></td>
      <td>Job นี้จะ<strong>ไม่รันอัตโนมัติ</strong> ต้องเข้าไปกดปุ่ม ▶️ (Play) เองที่หน้า Pipelines เสมอ เป็นการกันพลาดไม่ให้ Deploy ขึ้น Production โดยไม่ได้ตั้งใจ</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 5: เริ่มต้นใช้งาน (Execution)</h2>

<h3>5.1 Push Code ขึ้น GitLab</h3>
<pre><code class="language-bash"># ในโฟลเดอร์โปรเจกต์ PES ที่แตกไฟล์ไว้
git init
git remote add origin https://gitlab.vecskill.ovec/devops/pes.git
git add .
git commit -m "Setup CI/CD"
git branch -M main
git push -uf origin main
# ตอนถาม Username/Password ให้ใช้ Personal Access Token จากส่วนที่ 0 แทนรหัสผ่านบัญชี</code></pre>

<h3>5.2 ตรวจสอบ Pipeline และสั่ง Deploy</h3>
<ol>
  <li>ไปที่ GitLab Project <code>devops/pes</code> &gt; <strong>Build</strong> &gt; <strong>Pipelines</strong></li>
  <li>รอจนเห็นสถานะ <span style="color: green; font-weight: bold">Passed</span> ของ Stage <code>test</code> และ <code>build</code></li>
  <li>คลิกเข้าไปใน Pipeline แล้วกดปุ่ม ▶️ ที่ Job <code>deploy_prod</code> (เป็น Manual ตามที่ตั้งใจไว้ ป้องกัน Deploy พลาด)</li>
</ol>

<h3>5.3 ตรวจสอบผลลัพธ์บนเครื่อง Production</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>บริการ (Service)</th>
      <th>URL / ช่องทางเข้าถึง</th>
      <th>เกณฑ์การตรวจสอบความสำเร็จ</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Frontend Web Application (Nuxt)</td>
      <td><code>http://192.168.100.102:3000</code></td>
      <td>หน้าเว็บเปิดติดและเรียกข้อมูลจาก API ได้โดยไม่ติด CORS/Network Error</td>
    </tr>
    <tr>
      <td>Backend API Service</td>
      <td><code>http://192.168.100.102:7000</code></td>
      <td>บริการตอบสนอง HTTP Status ปกติและเชื่อมต่อฐานข้อมูล MySQL สำเร็จ</td>
    </tr>
    <tr>
      <td>phpMyAdmin (จัดการฐานข้อมูล)</td>
      <td><code>http://192.168.100.102:8080</code></td>
      <td>ล็อกอินด้วย <code>root</code> / <code>rootpassword</code> และพบฐานข้อมูล <code>skills_db</code> พร้อมตารางครบถ้วน</td>
    </tr>
  </tbody>
</table>
<p>หรือตรวจสอบตรงบนเครื่อง Production ด้วยคำสั่ง:</p>
<pre><code>docker ps</code></pre>
<p>(ควรเห็น Container <code>db</code>, <code>phpmyadmin</code>, <code>api</code>, <code>frontend</code> ทำงานอยู่ทั้งหมด)</p>
