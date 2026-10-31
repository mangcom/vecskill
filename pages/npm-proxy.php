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
  </li>
</ol>

<h3>ขั้นตอนที่ 1.2: ติดตั้งผ่าน Portainer Stack</h3>
<p>สร้างโฟลเดอร์สำหรับเก็บข้อมูลของ NPM</p>
<pre><code>sudo mkdir -p /srv/npm/data
sudo mkdir -p /srv/npm/letsencrypt</code></pre>
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
      - /srv/npm/data:/data
      - /srv/npm/letsencrypt:/etc/letsencrypt
    networks:
      - app_net

networks:
  app_net:
    external: true
</code></pre>
<p>เมื่อ Deploy Stack สำเร็จ NPM ก็พร้อมใช้งานแล้ว!</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: ตั้งค่าเริ่มต้นและทำ Reverse Proxy (HTTPS ภายนอก)</h2>

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

<h3>ขั้นตอนที่ 2.2: สร้าง Proxy Host สำหรับ GitLab</h3>
<p>เราจะตั้งค่าให้เมื่อมีคนเข้า <code>https://gitlab.vecskill.ovec</code> จะถูกส่งต่อไปยัง GitLab Container ของเรา</p>
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
    - <strong>SSL Certificate:</strong> เลือก "Request a new Certificate"<br />
    - <strong>เปิดใช้งาน `Force SSL`</strong> (เพื่อให้ redirect จาก http ไป https อัตโนมัติ)<br />
    <div class="image-container">
      <img src="images/08-npm-03-ssl.jpg" alt="NPM Proxy Host SSL" class="content-image" />
      <p><em>แท็บ SSL ของการสร้าง Proxy Host</em></p>
    </div>
  </li>
  <li>คลิก <strong>Save</strong></li>
</ol>
<p>
  รอสักครู่... NPM จะทำการขอ Certificate และตั้งค่า Nginx ให้โดยอัตโนมัติ เมื่อเสร็จแล้วลองเข้า <code>https://gitlab.vecskill.ovec</code> ผ่านเบราว์เซอร์ คุณจะพบว่าสามารถเข้าใช้งาน GitLab
  ผ่านโดเมนพร้อม HTTPS ได้แล้ว!
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: การทำ Internal HTTPS (ด้วย DNS Challenge)</h2>
<p>เช่นเดียวกับ Caddy, NPM ก็สามารถใช้ DNS Challenge เพื่อขอ Certificate ให้กับโดเมนที่ไม่เปิด Public ได้เช่นกัน ใน NPM เวอร์ชันปัจจุบัน ขั้นตอนนี้ถูกรวมไว้ในหน้าสร้าง/แก้ไข Proxy Host หน้าเดียวเลย ไม่ต้องไปสร้าง Credential แยกต่างหากอีกต่อไป</p>

<h3>ขั้นตอนที่ 3.1: สร้าง Proxy Host สำหรับ Service ภายใน</h3>
<ol>
  <li>ไปที่เมนู <strong>Hosts</strong> > <strong>Proxy Hosts</strong> > <strong>Add Proxy Host</strong></li>
  <li>
    <strong>ในแท็บ `Details`:</strong> กรอกข้อมูลเหมือนขั้นตอนที่ 2.2 แต่เปลี่ยน Domain Names เป็นโดเมนภายในของคุณ เช่น <code>wiki.internal.vecskill.ovec</code> และ Forward Hostname/IP
    ไปยัง Service ภายในของคุณ
  </li>
</ol>

<h3>ขั้นตอนที่ 3.2: เปิด DNS Challenge ในแท็บ SSL</h3>
<ol>
  <li>ไปที่แท็บ <strong>`SSL`</strong> แล้วเลือก <strong>SSL Certificate: "Request a new Certificate"</strong></li>
  <li>
    เปิดสวิตช์ <strong>`Use DNS Challenge`</strong> จะมีฟอร์มเพิ่มเติมปรากฏขึ้นมาในหน้าเดียวกันนี้เลย:
    <ul>
      <li><strong>DNS Provider:</strong> เลือก `Cloudflare` (หรือผู้ให้บริการ DNS ของคุณ)</li>
      <li>
        <strong>Credentials File Content:</strong> ใส่ API Token ของคุณตามรูปแบบนี้ (ตัวอย่าง placeholder ในช่องจะแสดงให้ดูอยู่แล้ว):
        <pre><code>dns_cloudflare_api_token = [YOUR_CLOUDFLARE_API_TOKEN]</code></pre>
      </li>
    </ul>
    <div class="image-container">
      <img src="images/08-npm-04-dns-challenge.jpg" alt="NPM DNS Challenge ในแท็บ SSL" class="content-image" />
      <p><em>เปิด Use DNS Challenge แล้วเลือก Cloudflare เป็น DNS Provider พร้อมใส่ Credentials File Content</em></p>
    </div>
  </li>
  <li>เปิดใช้งาน <strong>`Force SSL`</strong> แล้วคลิก <strong>Save</strong></li>
</ol>
<p>NPM จะใช้ API Token เพื่อสร้าง TXT Record ชั่วคราวและขอ Certificate มาให้โดยอัตโนมัติ เมื่อสำเร็จ Service ภายในของคุณก็จะพร้อมใช้งานผ่าน HTTPS ที่น่าเชื่อถือทันที แม้จะไม่ได้เปิด Public ก็ตาม</p>
