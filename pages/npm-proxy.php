<h1>บทที่ 8: ทางเลือก - การติดตั้งและใช้งาน Nginx Proxy Manager (NPM)</h1>
<p>
  Nginx Proxy Manager (NPM) คือเครื่องมือ Reverse Proxy ที่สร้างขึ้นบน Nginx ซึ่งเป็นเว็บเซิร์ฟเวอร์ที่ทรงพลังและมีเสถียรภาพสูง จุดเด่นที่สุดของ NPM คือมันมาพร้อมกับหน้าจอผู้ใช้งานบนเว็บ (Web UI)
  ที่ทำให้การตั้งค่า Proxy, การขอ SSL Certificate (HTTPS), และการจัดการ Access Control เป็นเรื่องง่ายๆ แค่ปลายนิ้วคลิก
</p>

<h3>Nginx Proxy Manager เทียบกับ Caddy</h3>
<ul>
  <li><strong>จุดเด่นของ NPM:</strong> ใช้งานง่ายมากผ่าน Web UI, มีฟีเจอร์เสริมเช่น Access Lists, เหมาะสำหรับผู้ที่ชอบการตั้งค่าแบบกราฟิก</li>
  <li><strong>จุดเด่นของ Caddy:</strong> คอนฟิกเป็นไฟล์ (`Caddyfile`) ซึ่งเหมาะกับการทำ Automation (Infrastructure as Code), ประสิทธิภาพสูง, ทันสมัยกว่า</li>
</ul>
<p>ในคู่มือนี้ เราจะติดตั้ง NPM ผ่าน Docker และตั้งค่าให้ทำงานร่วมกับ Service อื่นๆ บน <code>app_net</code> ของเรา</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>สำหรับการแข่งขัน:</strong> คู่มือนี้เน้นให้เข้าถึง Service ผ่าน HTTPS แบบ <strong>Intranet</strong> เป็นหลัก (เฉพาะเครื่องในทีมของคุณเข้าถึงได้ ไม่ต้องพึ่งอินเทอร์เน็ตหรือโดเมนสาธารณะเลย)
  ซึ่งเหมาะกับเครือข่ายปิดของสนามแข่งขันที่สุด ส่วนการเปิดให้เข้าถึงจากอินเทอร์เน็ตจริงเป็นทางเลือกเสริมไว้ท้ายหน้า
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: การติดตั้ง Nginx Proxy Manager</h2>
<p>NPM ประกอบด้วย 2 ส่วนหลัก คือตัวแอปพลิเคชันและฐานข้อมูล (ใช้ SQLite) การติดตั้งผ่าน Docker Compose / Portainer Stack จึงเป็นวิธีที่เหมาะสมที่สุด</p>

<h3>ขั้นตอนที่ 1.1: เตรียมความพร้อม</h3>
<ol>
  <li><strong>Network:</strong> ตรวจสอบให้แน่ใจว่าคุณได้สร้าง <code>app_net</code> ไว้แล้ว</li>
  <li>
    <strong>Firewall:</strong> ตรวจสอบว่า Firewall (UFW) ของคุณได้เปิด Port 80, 443, และ 81 ไว้แล้ว
    <pre><code># 80 และ 443 สำหรับ Proxy, 81 สำหรับหน้า Admin
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 81/tcp</code></pre>
  </li>
  <li style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 10px; list-style-position: inside">
    <strong>(ถ้าติดตั้ง Caddy ไว้) หยุด Caddy ชั่วคราว:</strong> หากคุณทำตามบทที่ 7 และ Caddy กำลังทำงานอยู่ ให้ทำการ Stop Stack ของ Caddy ก่อนเพื่อปลดการใช้งาน Port 80 และ 443
    (ใช้ตัวใดตัวหนึ่งระหว่าง Caddy กับ NPM เท่านั้น ไม่จำเป็นต้องรันพร้อมกันทั้งคู่)
  </li>
</ol>

<h3>ขั้นตอนที่ 1.2: ติดตั้งผ่าน Portainer Stack</h3>
<p>สร้างโฟลเดอร์สำหรับเก็บข้อมูลของ NPM (ใช้ <code>/opt/</code> เป็นที่เก็บมาตรฐาน สอดคล้องกับ Service อื่นๆ ในคู่มือนี้):</p>
<pre><code>sudo mkdir -p /opt/npm/data
sudo mkdir -p /opt/npm/letsencrypt</code></pre>
<p>จากนั้นไปที่ <strong>Stacks > + Add stack</strong>, ตั้งชื่อ (เช่น `npm-stack`) แล้วใช้โค้ด Docker Compose นี้:</p>
<pre><code>services:
  app:
    image: 'jc21/nginx-proxy-manager:latest'
    container_name: npm
    restart: always
    ports:
      # Public Ports
      - '80:80'
      - '443:443'
      # Admin UI Port
      - '81:81'
    volumes:
      - /opt/npm/data:/data
      - /opt/npm/letsencrypt:/etc/letsencrypt
    networks:
      - app_net

networks:
  app_net:
    external: true
</code></pre>
<p>เมื่อ Deploy Stack สำเร็จ NPM ก็พร้อมใช้งานแล้ว!</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: ตั้งค่าเริ่มต้น</h2>

<h3>ขั้นตอนที่ 2.1: สร้างบัญชี Admin ครั้งแรก</h3>
<p>NPM เวอร์ชันปัจจุบัน (2.x) ไม่ได้ใช้บัญชีเริ่มต้น <code>admin@example.com</code> / <code>changeme</code> แบบเวอร์ชันเก่าอีกต่อไป แต่จะบังคับให้สร้างบัญชี Admin ของตัวเองทันทีตั้งแต่ครั้งแรกที่เข้าใช้งาน</p>
<ol>
  <li>เปิดเว็บเบราว์เซอร์แล้วไปที่ <code>http://[IP_ADDRESS_SERVER_ของคุณ]:81</code></li>
  <li>
    หน้าจอ "Welcome! Get started by creating your admin account" จะปรากฏขึ้น ให้กรอก <strong>Full Name</strong>, <strong>Email address</strong> และ <strong>New Password</strong>
    (ตั้งรหัสผ่านที่คาดเดายาก) แล้วกด <strong>Save</strong>
    <div class="image-container">
      <img src="images/08-npm-01-create-admin.jpg" alt="NPM สร้างบัญชี Admin ครั้งแรก" class="content-image" />
      <p><em>หน้าจอสร้างบัญชี Admin ครั้งแรกของ Nginx Proxy Manager</em></p>
    </div>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: ทำ HTTPS ภายในทีม (Intranet) ด้วย mkcert</h2>
<p>
  NPM ไม่มีความสามารถเป็น Internal CA ในตัวเองแบบ Caddy แต่เราทำสิ่งเดียวกันได้ด้วยเครื่องมือเสริมชื่อ <strong>mkcert</strong> ซึ่งสร้าง Root CA ของเราเองขึ้นมา แล้วออก Certificate
  ที่เครื่อง Client เชื่อถือได้ทันทีหลังติดตั้ง Root CA เพียงครั้งเดียว โดยไม่ต้องมีโดเมนสาธารณะหรือพึ่งบริการ DNS ภายนอกเลย (หลักการเดียวกับบท
  <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ของ Caddy)
</p>

<h3>ขั้นตอนที่ 3.1: ติดตั้ง mkcert และสร้าง Certificate</h3>
<p>ทำบนเครื่องใดก็ได้ที่สะดวก (เครื่อง Server หรือเครื่องของคุณเองก็ได้ ขอแค่สามารถคัดลอกไฟล์ที่ได้ไปใช้ต่อได้)</p>
<pre><code># ติดตั้ง NSS tools ที่ mkcert ต้องใช้ (บน Ubuntu)
sudo apt update && sudo apt install -y libnss3-tools

# ดาวน์โหลด mkcert (เลือกเวอร์ชันล่าสุดจาก https://github.com/FiloSottile/mkcert/releases)
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert

# สร้าง Root CA ของเราเอง (ทำครั้งเดียว)
mkcert -install</code></pre>
<p>สร้าง Certificate แบบ Wildcard ให้ครอบคลุมทุกโดเมนย่อยในคราวเดียว (สะดวกกว่าออกทีละโดเมน):</p>
<pre><code>sudo mkdir -p /opt/npm/certs
cd /opt/npm/certs
mkcert -key-file npm-key.pem -cert-file npm-cert.pem "*.vecskill.ovec" vecskill.ovec</code></pre>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: ต้องระบุทั้ง <code>"*.vecskill.ovec"</code> (ครอบคลุมทุก Subdomain เช่น gitlab., portainer.) และ <code>vecskill.ovec</code> (ตัว Domain เปล่าๆ) เพราะ Wildcard ไม่ครอบคลุมตัว Domain หลักเอง</em>
</p>

<h3>ขั้นตอนที่ 3.2: ติดตั้ง Root CA บนเครื่อง Client</h3>
<p>
  หา Root CA ที่ mkcert สร้างไว้ด้วยคำสั่ง <code>mkcert -CAROOT</code> (ไฟล์ชื่อ <code>rootCA.pem</code>) แล้วคัดลอกไปติดตั้งบนเครื่อง Client ทุกเครื่องในทีม วิธีติดตั้ง (SCP/USB ไปไว้ที่เครื่อง Client
  แล้ว Import เข้า Trusted Root Store) เหมือนกับขั้นตอนที่ 3 ในบท
  <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ทุกประการ (เพียงแค่เปลี่ยนชื่อไฟล์จาก <code>caddy-root.crt</code> เป็น
  <code>rootCA.pem</code>) — ไม่ต้องทำซ้ำถ้าเครื่อง Client นั้นติดตั้ง Root CA ของ Caddy ไว้แล้วและเลือกใช้ CA เดียวกัน
</p>

<h3>ขั้นตอนที่ 3.3: อัปโหลด Certificate เข้า NPM</h3>
<ol>
  <li>ไปที่เมนู <strong>Certificates</strong> แล้วคลิก <strong>Add Certificate</strong> > เลือก <strong>Custom Certificate</strong></li>
  <li>
    กรอกข้อมูล:
    <ul>
      <li><strong>Name:</strong> ตั้งชื่อ (เช่น <code>vecskill-intranet</code>)</li>
      <li><strong>Certificate Key:</strong> เลือกไฟล์ <code>npm-key.pem</code> ที่สร้างไว้</li>
      <li><strong>Certificate:</strong> เลือกไฟล์ <code>npm-cert.pem</code> ที่สร้างไว้</li>
    </ul>
    <div class="image-container">
      <img src="images/08-npm-05-custom-cert.jpg" alt="NPM Add Custom Certificate" class="content-image" />
      <p><em>หน้าจอ Add Custom Certificate ของ Nginx Proxy Manager</em></p>
    </div>
  </li>
  <li>คลิก <strong>Save</strong></li>
</ol>

<h3>ขั้นตอนที่ 3.4: สร้าง Proxy Host สำหรับ GitLab โดยใช้ Certificate นี้</h3>
<ol>
  <li>ไปที่เมนู <strong>Hosts</strong> > <strong>Proxy Hosts</strong> แล้วคลิกปุ่ม <strong>Add Proxy Host</strong></li>
  <li>
    <strong>ในแท็บ `Details`:</strong><br />
    - <strong>Domain Names:</strong> พิมพ์ <code>gitlab.vecskill.ovec</code> แล้วกด Enter<br />
    - <strong>Scheme:</strong> เลือก <code>http</code><br />
    - <strong>Forward Hostname / IP:</strong> พิมพ์ <code>gitlab</code> (นี่คือชื่อ Service ของ GitLab Container ใน `app_net`)<br />
    - <strong>Forward Port:</strong> พิมพ์ <code>80</code><br />
    - <strong>เปิดใช้งาน `Block Common Exploits`</strong>
    <div class="image-container">
      <img src="images/08-npm-02-proxyhost-details.jpg" alt="NPM Proxy Host Details" class="content-image" />
      <p><em>แท็บ Details ของการสร้าง Proxy Host</em></p>
    </div>
  </li>
  <li>
    <strong>ในแท็บ `SSL`:</strong><br />
    - <strong>SSL Certificate:</strong> เลือก Certificate ที่เพิ่งสร้าง (<code>vecskill-intranet</code>) จากในรายการ แทนที่จะเลือก "Request a new Certificate"<br />
    - <strong>เปิดใช้งาน `Force SSL`</strong>
  </li>
  <li>คลิก <strong>Save</strong></li>
</ol>
<p>
  เท่านี้ก็เรียบร้อย เปิด <code>https://gitlab.vecskill.ovec</code> จากเครื่อง Client ที่ติดตั้ง Root CA ไว้แล้ว จะเข้าได้ปกติโดยไม่มีคำเตือนความปลอดภัยใดๆ และทำแบบนี้ซ้ำเพื่อสร้าง Proxy Host
  ให้ Service อื่นๆ ที่เหลือได้เลย (เลือก Certificate <code>vecskill-intranet</code> ตัวเดิมได้ทุกครั้ง เพราะเป็น Wildcard ครอบคลุมทุก Subdomain อยู่แล้ว)
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4 (ทางเลือกเสริม): เปิดให้เข้าถึงจากอินเทอร์เน็ตจริงด้วย Let's Encrypt</h2>
<p>
  ใช้วิธีนี้เฉพาะกรณีที่ต้องการให้คนนอกทีม (เช่น กรรมการที่ไม่ได้อยู่ในวง Intranet เดียวกัน) เข้าถึง Service ผ่านอินเทอร์เน็ตจริงได้ สำหรับการใช้งานภายในทีมตามปกติ ใช้วิธี mkcert ในส่วนที่ 3
  ก็เพียงพอแล้ว ไม่จำเป็นต้องทำส่วนนี้
</p>
<p>ต้องมี A Record ของโดเมนชี้มาที่ Public IP ของ Server ก่อน (ดูวิธีได้จากบท Caddy Reverse Proxy ส่วนที่ 2.1) แล้วสร้าง Proxy Host ตามปกติ:</p>
<ol>
  <li>ไปที่เมนู <strong>Hosts</strong> > <strong>Proxy Hosts</strong> แล้วคลิกปุ่ม <strong>Add Proxy Host</strong></li>
  <li>
    <strong>ในแท็บ `Details`:</strong> กรอกข้อมูลเหมือนขั้นตอนที่ 3.4 (Domain Names, Forward Hostname/IP, Forward Port, Block Common Exploits)
  </li>
  <li>
    <strong>ในแท็บ `SSL`:</strong><br />
    - <strong>SSL Certificate:</strong> เลือก "Request a new Certificate"<br />
    - <strong>เปิดใช้งาน `Force SSL`</strong> (เพื่อให้ redirect จาก http ไป https อัตโนมัติ)<br />
    <div class="image-container">
      <img src="images/08-npm-03-ssl.jpg" alt="NPM Proxy Host SSL" class="content-image" />
      <p><em>แท็บ SSL เมื่อเลือก Request a new Certificate</em></p>
    </div>
  </li>
  <li>คลิก <strong>Save</strong></li>
</ol>
<p>
  รอสักครู่... NPM จะทำการขอ Certificate จาก Let's Encrypt และตั้งค่า Nginx ให้โดยอัตโนมัติ เมื่อเสร็จแล้วลองเข้า <code>https://gitlab.vecskill.ovec</code> จากเครื่องนอกวง Intranet ก็จะเข้าได้ปกติ
</p>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: NPM ยังรองรับ "Let's Encrypt via DNS" (DNS Challenge ผ่าน Cloudflare เป็นต้น) เหมือน Caddy ด้วย เลือกตอน Add Certificate ได้โดยตรง แต่สำหรับ Intranet แนะนำให้ใช้วิธี mkcert
  ในส่วนที่ 3 เพราะไม่ต้องพึ่งบัญชี Cloudflare หรืออินเทอร์เน็ตเลย</em>
</p>
