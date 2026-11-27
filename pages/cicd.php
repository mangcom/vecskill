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
<p>ในแบบนี้ ชื่อ Image ทุกที่จะมี <code>:5000</code> ต่อท้ายโดเมนเสมอ เช่น <code>registry.vecskill.ovec:5000/vecskill-backend:latest</code></p>

<h4 style="color: #28a745">แบบที่ 2: ใช้ SSL ได้ (HTTPS ผ่าน Caddy Internal CA — แนะนำ ปลอดภัยกว่า)</h4>
<p>
  ถ้าทำตามบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ไว้แล้ว คุณจะเรียก <code>https://registry.vecskill.ovec</code> และ
  <code>https://registry-ui.vecskill.ovec</code> ได้โดยไม่มีคำเตือนจากเบราว์เซอร์ <strong>นี่คือแบบที่ตัวอย่างเต็มในส่วนที่ 3.5 ใช้เป็นค่าเริ่มต้น</strong> เพราะ Build ฝั่ง dind ใน Docker เวอร์ชันใหม่ๆ
  เชื่อถือ Certificate จาก CA สาธารณะ/Internal CA ที่เครื่อง Host เชื่อถืออยู่แล้วได้ตามปกติ โดยไม่ต้องใส่ Flag <code>--insecure-registry</code> เพิ่มเลย (ถ้า Build ยัง Error เรื่อง Certificate อยู่
  ให้สลับไปใช้แบบที่ 1 ชั่วคราวระหว่างแก้ไข)
</p>
<p>
  จุดที่ <strong>ต้องทำ</strong> สำหรับแบบนี้คือฝั่ง <strong>Production</strong> (ตอน <code>docker compose pull</code> ซึ่งรันบน Daemon จริงของเครื่อง ไม่ใช่ dind) ให้ติดตั้ง Root CA ของ Caddy ตามขั้นตอนที่
  3.4 ในบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> (ใช้ IP <code>10.20.30.101</code> เป็น Caddy Server และ
  <code>192.168.100.102</code> เป็น Production ตามตัวอย่างในบทนั้นพอดี) แล้วใช้ชื่อ Image ในไฟล์ <code>.gitlab-ci.yml</code>/<code>docker-compose.prod.yml</code> แบบไม่มี <code>:5000</code> ต่อท้าย
  เช่น <code>registry.vecskill.ovec/vecskill-backend:latest</code>
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
        <code>registry.vecskill.ovec</code> (แบบ HTTPS — ไม่มี Port, ค่าเริ่มต้นที่ใช้ในตัวอย่างนี้) หรือ <code>registry.vecskill.ovec:5000</code> (แบบ Insecure)
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
├── .gitlab-ci.yml            # ไฟล์ควบคุม Pipeline (Test → Build and push → Deploy)
├── .gitignore
├── docker-compose.yml        # รันพัฒนาบนเครื่อง Local (Build จากซอร์สโค้ดตรงๆ)
├── docker-compose_mysql.yml  # รันเฉพาะฐานข้อมูล MySQL+phpMyAdmin เผื่ออยาก Dev เร็วๆ โดยไม่ build API/Frontend
├── docker-compose.prod.yml   # ใช้ตอน Deploy บน Production (ดึง Image สำเร็จรูปจาก Registry มารัน ไม่ Build ที่เครื่องนี้)
├── schema.sql                # สคริปต์สร้างตารางฐานข้อมูล (ใช้กับ docker-compose.yml บน Local)
├── backend/                  # ซอร์สโค้ด API (Node.js/Express) + Dockerfile
├── frontend/                 # ซอร์สโค้ดเว็บ (Nuxt 3) + Dockerfile
└── deploy/                   # ไฟล์ "ไม่ลับ" ที่ Pipeline จะ rsync ไปเครื่อง Production เท่านั้น (ไม่เอาทั้ง Repo ไป)
    ├── schema.sql             # สำเนาของ schema.sql ไว้ให้ MySQL บน Production รันตอนเริ่มครั้งแรก
    └── .env.example           # เทมเพลตไม่มีค่าจริง ใช้ดูอ้างอิงตอนตั้งค่า .env.production ครั้งแรกบน Production เท่านั้น</code></pre>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ทำไมต้องมีโฟลเดอร์ <code>deploy/</code> แยกต่างหาก:</strong> Pipeline ไม่ได้ Clone ทั้ง Repo ไปไว้ที่ Production (ไม่จำเป็น เพราะ Production แค่รัน Image สำเร็จรูป ไม่ต้องมีซอร์สโค้ด)
  จึงส่งไปเฉพาะไฟล์ที่จำเป็นจริงๆ คือ <code>docker-compose.prod.yml</code> กับโฟลเดอร์ <code>deploy/</code> นี้เท่านั้น (ผ่านคำสั่ง <code>rsync</code> ในขั้นตอน Deploy)
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>ไฟล์ <code>.env.production</code> (ความลับจริง) ไม่อยู่ใน Repo เลย:</strong> สังเกตว่าโฟลเดอร์ <code>deploy/</code> ด้านบนมีแค่ <code>.env.example</code> (เทมเพลตเปล่า ไม่มีความลับ) เท่านั้น
  เพราะไฟล์ <code>.env</code> ตัวจริงถูก <code>.gitignore</code> กันไว้อยู่แล้ว (ดูบท <a href="#" onclick="document.querySelector('a[data-page=\'basic-git-usage\']').click();">การใช้งาน Git เบื้องต้น</a>)
  วิธีที่ถูกต้องคือสร้างไฟล์ <code>.env.production</code> <strong>ด้วยมือเพียงครั้งเดียว</strong> ไว้ที่เครื่อง Production โดยตรง (เช่น <code>/opt/vecskill/.env.production</code>) โดยอิง
  <code>deploy/.env.example</code> เป็นแนวทาง แล้ว Pipeline จะไม่ยุ่งหรือเขียนทับไฟล์นี้อีกเลยในทุกรอบ Deploy ถัดไป (ดูการใช้งานจริงในไฟล์ <code>.gitlab-ci.yml</code> ส่วนที่ 3.5 และคำอธิบายในส่วนที่ 4.4)
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
  แทน <code>build:</code> (สร้าง Image จากซอร์สโค้ด) และดึงค่าลับ (รหัสผ่าน DB, JWT Secret ฯลฯ) จากไฟล์ <code>.env.production</code> ที่อยู่บนเครื่อง Production เอง (ไม่ใช่จาก Repo):
</p>
<pre><code class="language-yaml">services:
  db:
    image: mysql:8.0
    restart: unless-stopped
    command: >
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      TZ: Asia/Bangkok
    volumes:
      - db_data:/var/lib/mysql
      - ./deploy/schema.sql:/docker-entrypoint-initdb.d/00-schema.sql:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-p${DB_ROOT_PASSWORD}"]
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
      PMA_PASSWORD: ${DB_ROOT_PASSWORD}
    ports: ["8080:80"]
    depends_on:
      db:
        condition: service_healthy
    networks: [prod_net]

  api:
    image: ${CI_REGISTRY}/vecskill-backend:${IMAGE_TAG:-latest}
    restart: unless-stopped
    env_file:
      - .env.production
    ports: ["7000:7000"]
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - ./uploads:/app/uploads
    networks: [prod_net]

  frontend:
    image: ${CI_REGISTRY}/vecskill-frontend:${IMAGE_TAG:-latest}
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
  <code>${DB_ROOT_PASSWORD}</code>/<code>${DB_NAME}</code> ถูกแทนค่าตอน Parse ไฟล์นี้ จากค่าที่อยู่ใน <code>.env.production</code> (ผ่าน Flag <code>--env-file .env.production</code> ตอนเรียก
  <code>docker compose</code> ในขั้นตอน Deploy) ส่วน Service <code>api</code> ใช้ <code>env_file: - .env.production</code> โดยตรง เพื่อส่งค่าทั้งหมด (เช่น <code>DB_HOST</code>, <code>DB_USER</code>,
  <code>DB_PASSWORD</code>, <code>JWT_SECRET</code> ตามที่ <code>backend/.env</code> ใช้บน Local) เข้าไปเป็น Environment Variable จริงๆ ของ Container — <code>deploy/schema.sql</code> คือไฟล์เดียวกับ
  <code>schema.sql</code> ที่ Root ของ Repo คัดลอกมาวางไว้
</p>

<h3>3.5 .gitlab-ci.yml (ไฟล์ Pipeline ฉบับสมบูรณ์)</h3>
<p>
  วางไฟล์นี้ไว้ที่ Root ของ Repo — ตัวอย่างนี้เขียนแบบใช้ <strong>HTTPS เป็นค่าเริ่มต้น</strong> (ดูหมายเหตุท้ายโค้ดสำหรับการสลับกลับไปใช้ Insecure Registry) คอมเมนต์ในโค้ดอธิบายทุกบรรทัดไว้แล้วว่าทำไมต้องเขียนแบบนี้:
</p>
<pre><code class="language-yaml">stages: [test, "build and push", deploy]

# ===== Global variables =====
variables:
  DOCKER_DRIVER: overlay2            # ให้ Docker-in-Docker ใช้ storage driver แบบ overlay2 (เร็วและเสถียรกว่า default)
  DOCKER_TLS_CERTDIR: "/certs"        # เปิด TLS ระหว่าง docker client กับ dind service (ค่ามาตรฐานของ image docker:26-dind)
  IMAGE_TAG: "${CI_COMMIT_SHORT_SHA}" # tag ของ image ที่ build = short SHA ของ commit นี้ (ไล่ย้อนได้ว่า image มาจาก commit ไหน)
  BACKEND_IMAGE: "${CI_REGISTRY}/vecskill-backend:${IMAGE_TAG}"   # ชื่อเต็มของ backend image ที่จะ push ขึ้น registry
  FRONTEND_IMAGE: "${CI_REGISTRY}/vecskill-frontend:${IMAGE_TAG}" # ชื่อเต็มของ frontend image ที่จะ push ขึ้น registry

  # === Deploy target (Production) ===
  DEPLOY_HOST: "192.168.100.102"  # IP/host ของเครื่อง production ที่จะ ssh เข้าไป deploy
  DEPLOY_PORT: "22"               # พอร์ต SSH ของเครื่อง production
  DEPLOY_USER: "devops"           # user ที่ใช้ ssh เข้าเครื่อง production
  DEPLOY_PATH: "/opt/vecskill"    # โฟลเดอร์ปลายทางบนเครื่อง production ที่เก็บไฟล์ compose/deploy

default:
  tags: [docker]                  # ใช้ runner ที่มี tag "docker" เท่านั้น (ต้องรองรับการรัน docker)
  cache:
    key: ${CI_COMMIT_REF_SLUG}    # แยก cache ต่อ branch/tag เพื่อไม่ให้ branch อื่นมาทับ cache กัน
    paths:
      - frontend/node_modules     # cache node_modules ของ frontend ไว้ ให้ npm ci รอบถัดไปเร็วขึ้น
      - backend/node_modules      # cache node_modules ของ backend ไว้เช่นกัน

# ===== TEST =====
test_backend:
  stage: test
  image: node:20-alpine                 # ใช้ Node เวอร์ชันเบา ๆ สำหรับรันเทส ไม่ต้องมี Docker
  script:
    - cd backend                        # เข้าไปที่โฟลเดอร์ backend ก่อนรันคำสั่ง npm
    - npm ci                            # ติดตั้ง dependency ตาม package-lock.json แบบ reproducible (เร็ว/นิ่งกว่า npm install)
    - npm run lint --if-present         # รัน lint ถ้ามีสคริปต์ "lint" ใน package.json เท่านั้น ไม่มีก็ข้ามไปเฉย ๆ
    - npm test --if-present             # รัน unit test ถ้ามีสคริปต์ "test" ใน package.json เท่านั้น
  rules:
    - changes: ["backend/**"]                          # รันเฉพาะตอนมีการแก้ไฟล์ใน backend/
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"  # หรือรันเสมอเมื่อเป็น pipeline ของ merge request

test_frontend:
  stage: test
  image: node:20-alpine
  script:
    - cd frontend                       # เข้าไปที่โฟลเดอร์ frontend ก่อนรันคำสั่ง npm
    - npm ci                            # ติดตั้ง dependency ตาม package-lock.json
    - npm run lint --if-present         # รัน lint ถ้ามีสคริปต์ "lint"
    - npm test --if-present             # รัน unit test ถ้ามีสคริปต์ "test"
  rules:
    - changes: ["frontend/**"]                          # รันเฉพาะตอนมีการแก้ไฟล์ใน frontend/
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"  # หรือรันเสมอเมื่อเป็น pipeline ของ merge request

# ===== BUILD AND PUSH (Docker-in-Docker) =====
# Registry is reached over HTTPS (registry.vecskill.ovec, no port, via the Caddy
# internal CA) — if you switch CI_REGISTRY back to the insecure :5000 form, add
# `command: ["--insecure-registry=registry.vecskill.ovec:5000"]` to the dind service.
.build_template:
  image: docker:26                # ใช้ docker CLI image เพื่อสั่ง build/push
  services:
    - docker:26-dind              # รัน Docker daemon แยกเป็น service ข้างใน job (Docker-in-Docker) ให้ docker CLI คุยด้วย
  before_script:
    - |
      if [ -n "$CI_REGISTRY_USER" ] && [ -n "$CI_REGISTRY_PASSWORD" ]; then  # ถ้ามีตัวแปร credential ของ registry (ตั้งไว้ใน CI/CD Variables)
        echo "$CI_REGISTRY_PASSWORD" | docker login -u "$CI_REGISTRY_USER" "$CI_REGISTRY" --password-stdin  # login เข้า registry โดยส่ง password ผ่าน stdin (ไม่หลุดไปอยู่ใน process list/log)
      fi

build_backend:
  stage: "build and push"
  extends: .build_template
  needs: ["test_backend"]          # รอให้ test_backend ผ่านก่อน ถึงจะเริ่ม build
  script:
    - docker build -t "$BACKEND_IMAGE" ./backend   # build image ของ backend จาก backend/Dockerfile ตั้ง tag ตาม commit sha
    - docker push "$BACKEND_IMAGE"                 # push image ที่เพิ่ง build ขึ้น registry
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then                                  # เฉพาะ branch main เท่านั้นที่จะอัปเดต tag "latest"
        docker tag "$BACKEND_IMAGE" "${CI_REGISTRY}/vecskill-backend:latest"     # ตั้งชื่อ tag เพิ่มเป็น :latest ให้ image เดียวกัน
        docker push "${CI_REGISTRY}/vecskill-backend:latest"                    # push tag :latest ขึ้น registry ด้วย
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["backend/**"]      # build เฉพาะตอนมีการแก้ไฟล์ใน backend/ และอยู่บน branch ใดก็ได้

build_frontend:
  stage: "build and push"
  extends: .build_template
  needs: ["test_frontend"]         # รอให้ test_frontend ผ่านก่อน ถึงจะเริ่ม build
  script:
    - docker build -t "$FRONTEND_IMAGE" ./frontend # build image ของ frontend จาก frontend/Dockerfile
    - docker push "$FRONTEND_IMAGE"                # push image ที่เพิ่ง build ขึ้น registry
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then                                   # เฉพาะ branch main เท่านั้นที่จะอัปเดต tag "latest"
        docker tag "$FRONTEND_IMAGE" "${CI_REGISTRY}/vecskill-frontend:latest"    # ตั้งชื่อ tag เพิ่มเป็น :latest ให้ image เดียวกัน
        docker push "${CI_REGISTRY}/vecskill-frontend:latest"                     # push tag :latest ขึ้น registry ด้วย
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["frontend/**"]     # build เฉพาะตอนมีการแก้ไฟล์ใน frontend/

# ===== DEPLOY (SSH to Production with docker compose) =====
deploy_prod:
  stage: deploy
  image: alpine:3.20
  needs:
    - job: build_backend
      optional: true              # ถ้า build_backend ไม่ได้รัน (เช่นไม่ได้แก้ backend/) ก็ยัง deploy ต่อได้
    - job: build_frontend
      optional: true              # เช่นเดียวกันกับฝั่ง frontend
  environment:
    name: production
    url: http://$DEPLOY_HOST:3000
    on_stop: stop_prod            # ถ้ากด Stop environment ใน GitLab จะเรียก job stop_prod ให้แทน
  before_script:
    - apk add --no-cache openssh-client rsync bash   # ติดตั้งเครื่องมือที่ต้องใช้ (ssh, rsync) บน alpine image ที่เบามาก ๆ
    - mkdir -p ~/.ssh && chmod 700 ~/.ssh            # เตรียมโฟลเดอร์เก็บ ssh key ให้สิทธิ์ตามที่ ssh ต้องการ
    - echo "$DEPLOY_PRIV_KEY" > ~/.ssh/id_ed25519    # เขียน private key (เก็บเป็น CI/CD Variable แบบ masked/protected) ลงไฟล์
    - chmod 600 ~/.ssh/id_ed25519                    # จำกัดสิทธิ์ไฟล์ key ให้อ่านได้เฉพาะเจ้าของ ไม่งั้น ssh จะปฏิเสธใช้ key นี้
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" >> ~/.ssh/known_hosts || true  # เพิ่ม host key ของปลายทางล่วงหน้า กันค้างรอ prompt yes/no (|| true กันพังถ้า keyscan ล้มเหลว)
    - export REMOTE_DIR="${DEPLOY_PATH}"             # ตั้งตัวแปรย่อไว้ใช้อ้างอิงต่อในขั้นตอนถัดไป
  script:
    # Only docker-compose.prod.yml + deploy/ go to Production — never the source
    # tree, since Production just pulls ready-made images from the registry.
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "sudo mkdir -p $REMOTE_DIR/deploy && sudo chown -R \$USER:\$USER $REMOTE_DIR"  # สร้างโฟลเดอร์ปลายทางถ้ายังไม่มี แล้วเปลี่ยนเจ้าของให้ user ที่ deploy ใช้ จะได้ rsync ได้โดยไม่ต้อง sudo ทุกครั้ง
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" docker-compose.prod.yml "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/"  # ส่งไฟล์ compose สำหรับ production ไปที่เครื่องปลายทาง
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" deploy/schema.sql deploy/.env.example "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/deploy/"  # ส่งไฟล์ schema ตั้งต้นและไฟล์ตัวอย่าง .env ไปด้วย (ไม่ใช่ .env จริงที่มี secret)
    - |
      if [ -n "$CI_REGISTRY_USER" ] && [ -n "$CI_REGISTRY_PASSWORD" ]; then  # ถ้ามี credential ของ registry
        ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "echo \"$CI_REGISTRY_PASSWORD\" | docker login -u \"$CI_REGISTRY_USER\" \"$CI_REGISTRY\" --password-stdin"  # ให้เครื่อง production login เข้า registry ด้วย (จำเป็นถ้า registry เป็น private)
      fi
    # .env.production (DB secrets + deploy/.env itself) is created once, by hand,
    # on the Production host — it is never generated or overwritten by this job.
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd $REMOTE_DIR && CI_REGISTRY='${CI_REGISTRY}' IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000' docker compose -f docker-compose.prod.yml --env-file .env.production pull"  # ให้เครื่อง production ดึง (pull) image เวอร์ชันล่าสุดจาก registry มาเตรียมไว้ก่อน
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd $REMOTE_DIR && CI_REGISTRY='${CI_REGISTRY}' IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000' docker compose -f docker-compose.prod.yml --env-file .env.production up -d --remove-orphans"  # รัน/รีสตาร์ท container ด้วย image ที่เพิ่ง pull มา และลบ container เก่าที่ไม่อยู่ใน compose แล้วทิ้ง (--remove-orphans)
  rules:
    # Deploy only on main, and only manually (Play button on the Pipelines page) —
    # guards against an accidental push to Production.
    - if: '$CI_COMMIT_BRANCH == "main"'
      when: manual                # ต้องกดรันเองจากหน้า Pipelines เท่านั้น ไม่ deploy อัตโนมัติ
      allow_failure: false        # ถ้า job นี้ fail ให้ pipeline แสดงว่า fail จริง ๆ ไม่ข้ามเฉย ๆ

# ===== STOP Production (optional) =====
stop_prod:
  stage: deploy
  image: alpine:3.20
  environment:
    name: production
    action: stop                  # บอก GitLab ว่า job นี้คือการ "หยุด" environment production
  before_script:
    - apk add --no-cache openssh-client          # ติดตั้งแค่ ssh client (ไม่ต้องใช้ rsync ในขั้นตอนนี้)
    - mkdir -p ~/.ssh && chmod 700 ~/.ssh         # เตรียมโฟลเดอร์เก็บ ssh key
    - echo "$DEPLOY_PRIV_KEY" > ~/.ssh/id_ed25519 # เขียน private key ลงไฟล์
    - chmod 600 ~/.ssh/id_ed25519                 # จำกัดสิทธิ์ไฟล์ key
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" >> ~/.ssh/known_hosts || true  # เพิ่ม host key ล่วงหน้า กันค้างรอ prompt
  script:
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd ${DEPLOY_PATH} && docker compose -f docker-compose.prod.yml down"  # สั่งหยุดและลบ container ทั้งหมดของ production (docker compose down)
  rules:
    - when: manual                # ต้องกดรันเองเท่านั้น ไม่มีทางรันอัตโนมัติ
      allow_failure: true         # ถ้า fail ก็ไม่ทำให้ pipeline โดยรวมแสดงว่า fail</code></pre>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>สลับกลับไปใช้ Insecure Registry แทน HTTPS:</strong> แก้แค่ 2 จุด — (1) เพิ่มบรรทัด <code>command: ["--insecure-registry=registry.vecskill.ovec:5000"]</code> เข้าไปใต้
  <code>services: - docker:26-dind</code> ใน <code>.build_template</code> (2) เปลี่ยนค่า Group Variable <code>CI_REGISTRY</code> จาก <code>registry.vecskill.ovec</code> เป็น
  <code>registry.vecskill.ovec:5000</code> — ที่เหลือไม่ต้องแก้อะไรเลย เพราะชื่อ Image ทุกจุดอ้างอิงผ่านตัวแปร <code>$CI_REGISTRY</code> อยู่แล้ว
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
      <td><code>DOCKER_DRIVER: overlay2</code></td>
      <td>ให้ Docker-in-Docker ใช้ Storage Driver แบบ <code>overlay2</code> (เร็วและเสถียรกว่าแบบ Default)</td>
    </tr>
    <tr>
      <td><code>DOCKER_TLS_CERTDIR: "/certs"</code></td>
      <td>เปิด TLS ระหว่าง Docker Client กับ dind Service — เป็นค่ามาตรฐานของ Image <code>docker:26-dind</code> (คนละเรื่องกับ TLS ของ Registry ที่คุยกันในข้อ 1.1)</td>
    </tr>
    <tr>
      <td><code>IMAGE_TAG: "${CI_COMMIT_SHORT_SHA}"</code></td>
      <td>ใช้ Hash ของ Commit (ย่อ) เป็น Tag ของ Image แต่ละรอบ Build แทนการใช้ <code>latest</code> ตลอด เพื่อให้ Image แต่ละเวอร์ชันแยกจากกันชัดเจน ย้อนดู/ย้อนกลับได้</td>
    </tr>
    <tr>
      <td><code>BACKEND_IMAGE</code> / <code>FRONTEND_IMAGE</code></td>
      <td>ประกอบชื่อ Image เต็มจาก <code>$CI_REGISTRY</code> (Group Variable ที่ตั้งไว้) + ชื่อ Image แบบชั้นเดียว (<code>vecskill-backend</code>, <code>vecskill-frontend</code>) + Tag — ใช้ชื่อแบบชั้นเดียวเพื่อไม่ให้สับสนกับ
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

<h3>4.3 Stage: build_backend / build_frontend (Docker-in-Docker)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ส่วนของโค้ด</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>services:<br />&nbsp;&nbsp;- docker:26-dind</code></td>
      <td>เปิด Docker Daemon ชั่วคราวขึ้นมาให้ Job นี้ (ไม่มี Flag เสริมใดๆ) เพราะ Registry อยู่หลัง HTTPS/Caddy Internal CA แล้ว dind เชื่อถือได้ตามปกติ — ถ้าสลับไปใช้ Insecure Registry
      ต้องเพิ่ม <code>command: ["--insecure-registry=registry.vecskill.ovec:5000"]</code> เข้ามาด้วย (ดูหมายเหตุท้ายโค้ดในข้อ 3.5)</td>
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
      <td>Tag ชั่วคราว (ตาม Commit) ถูก Push ทุกครั้ง แต่ Tag <code>latest</code> (ที่ฝั่ง Deploy จะไปดึงมาใช้) จะถูกอัปเดตเฉพาะตอน Push เข้า <code>main</code> เท่านั้น กัน Branch ทดลองไปทับ Production
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
      <td><code>needs: - job: build_backend<br />&nbsp;&nbsp;optional: true</code></td>
      <td><strong>จุดสำคัญ:</strong> ใช้ <code>optional: true</code> แทนการบังคับทั้งคู่แบบเดิม เพราะถ้า Commit นั้นแก้แค่ <code>frontend/</code> (ตาม <code>rules: changes</code> ในข้อ 4.3) Job
      <code>build_backend</code> จะไม่ถูกสร้างขึ้นมาเลยในรอบนั้น ถ้าใช้ <code>needs</code> แบบบังคับ Pipeline จะ Error ทันทีว่าหา Job ที่ต้องรอไม่เจอ</td>
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
      <td><code>rsync ... docker-compose.prod.yml ...</code><br /><code>rsync ... deploy/schema.sql deploy/.env.example ...</code></td>
      <td>ส่งเฉพาะไฟล์ที่จำเป็นสำหรับรัน Production ไปที่เครื่องปลายทาง (ไม่ส่งซอร์สโค้ด <code>backend/</code>/<code>frontend/</code> ไปด้วย เพราะ Production ใช้ Image สำเร็จรูปจาก Registry อยู่แล้ว) —
      สังเกตว่าส่งแค่ <code>.env.example</code> (เทมเพลตเปล่า) ไม่ใช่ <code>.env</code> ตัวจริง เพราะไฟล์ลับจริงต้องตั้งไว้ที่เครื่อง Production เองแล้วตั้งแต่แรก (ดูข้อ 3.1)</td>
    </tr>
    <tr>
      <td><code>docker compose ... --env-file .env.production pull</code></td>
      <td>สั่งให้ Production ดึง Image Tag <code>latest</code> เสมอ (ไม่ใช่ Tag ตาม Commit Hash ของรอบนี้) เพื่อให้ตรงกับ Tag ที่ Job Build อัปเดตไว้ในข้อ 4.3 โดยไม่ต้องส่งค่า Commit Hash ข้ามมาให้ตรงกันเป๊ะ
      Flag <code>--env-file .env.production</code> บอก Docker Compose ให้อ่านค่าตัวแปร (เช่น <code>DB_ROOT_PASSWORD</code>) จากไฟล์ที่สร้างไว้ด้วยมือบนเครื่องนี้แล้ว (ไม่ใช่จากไฟล์ที่ Pipeline ส่งมา)</td>
    </tr>
    <tr>
      <td><code>CI_REGISTRY='${CI_REGISTRY}' IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='...'</code></td>
      <td>Export ตัวแปรเหล่านี้ไว้ในฝั่ง Shell ของเครื่อง Production ก่อนเรียก <code>docker compose</code> เพื่อให้ Docker Compose แทนค่า <code>${CI_REGISTRY}</code>/<code>${IMAGE_TAG}</code>
      ใน <code>image:</code> ของ <code>docker-compose.prod.yml</code> ได้ถูกต้อง (คนละตัวแปรกับที่มาจาก <code>--env-file .env.production</code> ด้านบน)</td>
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
