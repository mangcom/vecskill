<h1>การติดตั้ง Registry Server + UI</h1>
<p>
    Private Docker Registry คือพื้นที่สำหรับจัดเก็บ Docker Image ของเราเองแบบส่วนตัว ในคู่มือนี้ เราจะใช้โดเมน <strong>registry.vecskill.ovec</strong> เป็นตัวอย่างในการตั้งค่าทั้งหมด และจะให้ทำงานบน Network กลางชื่อ <code>app_net</code> เพื่อให้สามารถทำงานร่วมกับ Service อื่นๆ เช่น GitLab ได้ในอนาคต
</p>

<h2 style="color: #e74c3c;">สิ่งที่ต้องทำก่อน: การตั้งค่า DNS และ Network</h2>
<ol>
    <li><strong>ตั้งค่า DNS:</strong> คุณต้องสร้าง A Record ในระบบ DNS ของ `vecskill.ovec` ให้ชี้มายัง IP Address ของ Server:
        <pre><code>Host/Name: registry
Type: A
Value/Points to: [IP_ADDRESS_ของ_SERVER_คุณ]</code></pre>
    </li>
    <li><strong>สร้าง Network:</strong> (ทำครั้งเดียว) ตรวจสอบให้แน่ใจว่าคุณได้สร้าง Network กลางไว้แล้วด้วยคำสั่ง:
        <pre><code>sudo docker network create app_net</code></pre>
    </li>
</ol>


<hr style="margin: 40px 0;">

<h2>ส่วนที่ 1: ติดตั้งด้วยตนเอง (Manual Installation)</h2>
<p>
    วิธีนี้เป็นการใช้คำสั่ง `docker run` เพื่อสร้างแต่ละ Container โดยเราจะกำหนดให้ Container ทั้งสองเชื่อมต่อเข้ากับ <code>app_net</code>
</p>
<h3>ขั้นตอนที่ 1.1: ติดตั้ง Docker Registry Server</h3>
<ol>
    <li>
        <strong>สร้างโฟลเดอร์สำหรับเก็บข้อมูล Image:</strong><br>
        <pre><code>sudo mkdir -p /mydata/registry</code></pre>
    </li>
    <li>
        <strong>รัน Container ของ Registry Server บน `app_net`:</strong><br>
        <pre><code>sudo docker run -d \
    -p 5000:5000 \
    --name registry \
    --network app_net \
    --restart=always \
    -e REGISTRY_STORAGE_DELETE_ENABLED=true \
    -v /mydata/registry:/var/lib/registry \
    registry:2</code></pre>
    </li>
</ol>

<h3>ขั้นตอนที่ 1.2: ติดตั้ง Registry UI (User Interface)</h3>
<pre><code>sudo docker run -d \
    -p 8080:80 \
    --name registry-ui \
    --network app_net \
    --restart=always \
    -e REGISTRY_URL=http://registry:5000 \
    -e DELETE_IMAGES=true \
    joxit/docker-registry-ui:latest</code></pre>


<hr style="margin: 40px 0;">

<h2>ส่วนที่ 2: การติดตั้งผ่าน Portainer Stack (วิธีแนะนำ)</h2>
<p>
    เราจะแก้ไขไฟล์ Docker Compose เพื่อระบุให้ Stack นี้เชื่อมต่อกับ Network ภายนอกที่ชื่อว่า <code>app_net</code>
</p>

<h3>ขั้นตอนที่ 2.1: สร้าง Docker volume</h3>
<pre><code>sudo docker volume create registry_data</code></pre>

<h3>ขั้นตอนที่ 2.2: โค้ด Docker Compose สำหรับ Stack</h3>
<pre><code>
version: '3.8'    # กำหนดเวอร์ชันของ Docker Compose ปัจจุบันไม่จำเป็นต้องระบุเวอร์ชันก็ได้

networks:
  app_net:
    external: true

volumes:
  registry_data:
    external: true

services:
  registry:
    image: registry:2
    container_name: registry
    restart: always
    ports:
      - "5000:5000"
    environment:
      REGISTRY_STORAGE_DELETE_ENABLED: 'true'
    networks:
      - app_net
    volumes:
      - registry_data:/var/lib/registry

  registry-ui:
    image: joxit/docker-registry-ui:main
    container_name: registry-ui
    restart: always
    networks:
      - app_net
    ports:
      - "8080:80"
    environment:
      - SINGLE_REGISTRY=true
      - REGISTRY_TITLE=Docker Registry UI
      - DELETE_IMAGES=true
      - SHOW_CONTENT_DIGEST=true
      - NGINX_PROXY_PASS_URL=http://registry:5000
      - SHOW_CATALOG_NB_TAGS=true
      - CATALOG_MIN_BRANCHES=1
      - CATALOG_MAX_BRANCHES=1
      - TAGLIST_PAGE_SIZE=100
      - REGISTRY_SECURED=false
      - CATALOG_ELEMENTS_LIMIT=1000
    depends_on:
      - registry
</code></pre>
<p><strong>คำอธิบายการเปลี่ยนแปลง:</strong></p>
<ul>
    <li><code>networks: - app_net</code>: ถูกเพิ่มเข้าไปใน <strong>ทุก Service</strong> เพื่อสั่งให้ Container นั้นๆ เชื่อมต่อกับ <code>app_net</code></li>
    <li><code>networks: app_net: external: true</code>: เป็นการประกาศที่ท้ายไฟล์ เพื่อบอก Docker Compose ว่า "app_net เป็น Network ที่มีอยู่แล้วจากภายนอกนะ ไม่ต้องสร้างใหม่"</li>
</ul>

<h3>ขั้นตอนที่ 2.3: การติดตั้งบน Portainer</h3>
<ol>
    <li>ไปที่เมนู <strong>Stacks</strong> > <strong>+ Add stack</strong> และตั้งชื่อ (เช่น `registry-stack`)</li>
    <li>ในส่วน Build method เลือก <strong>Web editor</strong> และนำโค้ดด้านบนไปวาง</li>
    <li>คลิกปุ่ม <strong>Deploy the stack</strong></li>
</ol>
<h2>ตัวอย่างการสร้าง Stack</h2>
<p>คลิกที่ [Stacks] -> [+ Add stack]</p>
<div class="image-container">
  <img src="images/03-registry-01.png" alt="Add Stack" class="content-image">
  <p><em>Add Stack</em></p>
</div>
<p>ตั้งชื่อ stack  และนำ Compose วางในช่่อง Web editor ตัวอย่าง Name: registry-stack</p>
<div class="image-container">
  <img src="images/03-registry-02.png" alt="Create Stack" class="content-image">
  <p><em>Create Stack</em></p>
</div>
<p>คลิกที่ [Deploy the stack]</p>
<div class="image-container">
  <img src="images/03-registry-03.png" alt="Deploy the stack" class="content-image">
  <p><em>Deploy the stack</em></p>
</div>
<p>เมื่อสร้าง Stack เสร็จ คลิกที่ชื่อ registry-stack เพื่อดูรายละเอียด</p>
<div class="image-container">
  <img src="images/03-registry-04.png" alt="Stacks list" class="content-image">
  <p><em>Stacks list</em></p>
</div>
<p>รายละเอียดของ stack สังเกต ที่ ชื่อ Service และ Port</p>
<div class="image-container">
  <img src="images/03-registry-05.png" alt="Stack details" class="content-image">
  <p><em>Stack detail</em></p>
</div>
<h2>ตัวอย่างการเรียกดู Registry UI</h2>
<p>เปิด Browser แล้วเข้าไปยัง [ip registry server]:[port] ตัวอย่าง http://devops-server.vecskill.ovec:8080 </p>
<div class="image-container">
  <img src="images/03-registry-06.png" alt="Docker Registry UI" class="content-image">
  <p><em>Docker Registry UI</em></p>
</div>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 3: การตั้งค่า Client และวิธีการใช้งาน</h2>
<p>
    ส่วนนี้จะเหมือนเดิมทุกประการ คุณยังคงต้องตั้งค่า <code>daemon.json</code> เพื่อให้ Docker Client ของคุณเชื่อใจ <code>registry.vecskill.ovec:5000</code> และใช้คำสั่ง `docker push` ตามปกติ
</p>

<h4 style="color: #c0392b;">ขั้นตอนการตั้งค่า Docker Daemon (เลือกทำตามระบบของคุณ)</h4>
<style>
.tab { overflow: hidden; border-bottom: 1px solid #ccc; background-color: #f1f1f1; border-radius: 5px 5px 0 0;}
.tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; font-size: 17px; font-family: 'Sarabun', sans-serif;}
.tab button:hover { background-color: #ddd; }
.tab button.active { background-color: #ccc; font-weight: bold;}
.tabcontent { display: none; padding: 20px 12px; border: 1px solid #ccc; border-top: none; border-radius: 0 0 5px 5px; background-color: white;}
</style>
<div class="tab">
  <button class="tablinks  active" data-target-tab="Desktop">สำหรับ Docker Desktop (Windows/Mac)</button>
  <button class="tablinks" data-target-tab="Linux">สำหรับ Linux / WSL2</button>
</div>
<div id="Linux" class="tabcontent" style="display: block;">
  <h3>สำหรับผู้ใช้งาน Linux (เช่น Ubuntu) หรือ Docker Engine บน WSL2</h3>
  <ol>
    <li><strong>เปิดไฟล์ `daemon.json`:</strong> <pre><code>sudo nano /etc/docker/daemon.json</code></pre></li>
    <li><strong>เพิ่มคอนฟิก:</strong> <pre><code>{
"insecure-registries": ["registry.vecskill.ovec:5000"]
}</code></pre></li>
    <li><strong>บันทึกไฟล์และออกจาก nano</strong></li>
    <li><strong>รีสตาร์ท Docker Service:</strong> <pre><code>sudo systemctl restart docker</code></pre></li>
  </ol>
</div>
<div id="Desktop" class="tabcontent">
  <h3>สำหรับผู้ใช้งาน Docker Desktop (บน Windows และ macOS)</h3>
  <ol>
    <li><strong>เปิด Settings</strong> > <strong>Docker Engine</strong> และแก้ไขคอนฟิก JSON <pre><code>{
"insecure-registries": ["registry.vecskill.ovec:5000"]
}</code></pre>

<div class="image-container">
  <img src="images/03-registry-07.png" alt="Docker Engine Settings" class="content-image">
  <p><em>Docker Engine Settings</em></p>
</div>
</li>
    <li><strong>บันทึกและรีสตาร์ท:</strong> คลิกปุ่ม <strong>Apply & Restart</strong></li>
  </ol>
</div>
<hr style="margin: 30px 0;">
<h4>ขั้นตอนการ Tag และ Push Image</h4>
<ol>
    <li><strong>Tag Image ใหม่:</strong> <pre><code>docker tag ubuntu:latest registry.vecskill.ovec:5000/devops-os:v1</code></pre></li>
    <li><strong>Push Image ไปยัง Private Registry:</strong><pre><code>docker push registry.vecskill.ovec:5000/devops-os:v1</code></pre></li>
</ol>
<hr style="margin: 30px 0;">
<h4>ตัวอย่างการใช้งาน</h4>
<p>
  <ul class="b">
    <li>ใช้ คำสั่ง docker images เพื่อ แสดงรายการ images</li>
    <li>ใช้ คำสั่ง docker pull เพื่อดึง image จาก registry</li>
    <li>ใช้ คำสั่ง docker tag เพื่อ tag image</li>
    <li>ใช้ คำสั่ง docker push เพื่อส่ง image ขึ้น registry</li>
  </ul>
</p>
<div class="image-container">
  <img src="images/03-registry-08.png" alt="docker pull tag" class="content-image">
  <p><em>docker pull tag</em></p>
</div>
<div class="image-container">
  <img src="images/03-registry-09.png" alt="docker push" class="content-image">
  <p><em>docker push</em></p>
</div>
<div class="image-container">
  <img src="images/03-registry-10.png" alt="webui-index" class="content-image">
  <p><em>webui-index</em></p>
</div>
<div class="image-container">
  <img src="images/03-registry-11.png" alt="devops-os-image" class="content-image">
  <p><em>devops-os-image</em></p>
</div>