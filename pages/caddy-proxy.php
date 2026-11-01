<h1>บทที่ 7: การติดตั้งและใช้งาน Caddy Reverse Proxy</h1>
<p>
  ณ ตอนนี้ Service ต่างๆ ของเราเข้าถึงผ่าน Port ที่แตกต่างกัน (เช่น GitLab ที่ Port 80, Portainer ที่ 9443) ซึ่งไม่สะดวกและไม่ปลอดภัยนัก Caddy จะเข้ามาทำหน้าที่เป็น "ผู้จัดการด้านหน้า" หรือ Reverse
  Proxy คอยรับการจราจรทั้งหมดที่เข้ามาทาง Port มาตรฐาน (80 และ 443) แล้วแจกจ่ายไปยัง Service ที่ถูกต้องภายใน Server ของเราโดยอัตโนมัติ
</p>

<h3>ทำไมต้องใช้ Caddy Reverse Proxy?</h3>
<ul>
  <li><strong>เข้าถึงง่าย:</strong> เราสามารถเข้าถึงทุก Service ผ่านโดเมนสวยๆ ได้เลย เช่น <code>https://gitlab.vecskill.ovec</code> โดยไม่ต้องจำ Port อีกต่อไป</li>
  <li><strong>HTTPS อัตโนมัติ:</strong> ไม่ว่าจะใช้งานภายในทีม (Intranet) หรือเปิดสู่อินเทอร์เน็ตจริง Caddy ก็จัดการเรื่อง Certificate ให้อัตโนมัติทั้งคู่ โดยไม่ต้องตั้งค่าอะไรเพิ่มเติมเลย</li>
  <li><strong>จัดการทุกอย่างในที่เดียว:</strong> ตั้งค่าโดเมนทั้งหมดของเราได้ในไฟล์ `Caddyfile` เพียงไฟล์เดียว</li>
  <li><strong>เพิ่มความปลอดภัย:</strong> ซ่อนโครงสร้างและ Port ภายในของ Server ของเราจากโลกภายนอก</li>
</ul>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>สำหรับการแข่งขัน:</strong> คู่มือนี้เน้นให้เข้าถึง Service ผ่าน HTTPS แบบ <strong>Intranet</strong> เป็นหลัก (เฉพาะเครื่องในทีมของคุณเข้าถึงได้ ไม่ต้องพึ่งอินเทอร์เน็ตหรือโดเมนสาธารณะเลย)
  ซึ่งเหมาะกับเครือข่ายปิดของสนามแข่งขันที่สุด ส่วนการเปิดให้เข้าถึงจากอินเทอร์เน็ตจริงเป็นทางเลือกเสริมไว้ท้ายหน้า
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: การทำ Reverse Proxy + HTTPS สำหรับใช้งานภายในทีม (Intranet)</h2>
<p>
  วิธีนี้ใช้ความสามารถของ Caddy ในการเป็น <strong>Internal CA (Certificate Authority) ของตัวเอง</strong> ออก Certificate ที่เชื่อถือได้สำหรับโดเมนภายใน โดยไม่ต้องมีโดเมนสาธารณะ ไม่ต้องเปิด Port 80/443
  ออกอินเทอร์เน็ต และไม่ต้องพึ่งบริการ DNS ภายนอกใดๆ เลย
</p>

<h3>ขั้นตอนที่ 1.1: เตรียมความพร้อม</h3>
<ol>
  <li>
    <strong>ตั้งชื่อโดเมนภายใน:</strong> กำหนดชื่อที่จะใช้เรียก Service แต่ละตัว เช่น <code>gitlab.vecskill.ovec</code>, <code>portainer.vecskill.ovec</code> แล้วทำให้เครื่องในทีมเรียกชื่อเหล่านี้แล้ววิ่งมาหา IP
    ของ Server ที่รัน Caddy ได้ ทำได้ 2 วิธี:
    <ul>
      <li><strong>แนะนำ:</strong> ใช้ DNS Server ภายใน (ดูวิธีได้จากบท <a href="#" onclick="document.querySelector('a[data-page=\'technitium-dns\']').click();">การติดตั้ง DNS Server</a>) แล้วแจก DNS นี้ให้ทุกเครื่องในทีมผ่าน DHCP</li>
      <li><strong>ทดสอบเร็วๆ:</strong> แก้ไฟล์ <code>hosts</code> บนเครื่อง Client แต่ละเครื่องให้ชี้โดเมนไปที่ IP ของ Server โดยตรง (ไม่ยืดหยุ่นเท่า DNS Server แต่ไม่ต้องตั้งค่าเพิ่ม)</li>
    </ul>
  </li>
  <li>
    <strong>เปิด Firewall:</strong> เปิดเฉพาะ Port 443 ก็พอสำหรับใช้งานภายในทีม (ไม่จำเป็นต้องเปิด 80 ออกสู่ภายนอกเลย)
    <pre><code>sudo ufw allow 443/tcp</code></pre>
  </li>
  <li style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 10px; list-style-position: inside">
    <strong>ลบ Port Bindings เก่า:</strong> เข้าไปแก้ไข Stack ของ GitLab และ Portainer เพื่อ <strong>ลบหรือคอมเมนต์</strong> ส่วน <code>ports</code> ที่ผูกกับ Port 80, 443, และ 9443 ออกไป เพราะ Caddy
    จะเป็นผู้จัดการ Port เหล่านี้แทนแล้ว ตัวอย่างการแก้ไขใน Stack GitLab:
    <pre><code># ใน gitlab-stack ของคุณ
services:
  gitlab:
    # ...
    # ports:             <-- คอมเมนต์ หรือลบบรรทัดเหล่านี้ทิ้ง
    #   - '80:80'
    #   - '443:443'
    # ...
</code></pre>
    หลังจากแก้ไขแล้ว ให้ทำการ Update Stack เพื่อให้การเปลี่ยนแปลงมีผล
  </li>
</ol>

<h3>ขั้นตอนที่ 1.2: สร้างไฟล์คอนฟิก `Caddyfile`</h3>
<p>สร้างโฟลเดอร์สำหรับ Caddy และสร้างไฟล์ `Caddyfile` ขึ้นมา (ใช้ <code>/opt/</code> เป็นที่เก็บมาตรฐาน สอดคล้องกับ Service อื่นๆ ในคู่มือนี้):</p>
<pre><code># สร้างโฟลเดอร์
sudo mkdir -p /opt/caddy

# สร้างและแก้ไขไฟล์ Caddyfile
sudo nano /opt/caddy/Caddyfile</code></pre>
<p>คัดลอกเนื้อหานี้ไปวางในไฟล์ `Caddyfile` สังเกต Directive <code>tls internal</code> ที่บอกให้ Caddy ออก Certificate เองแทนการขอจาก Let's Encrypt:</p>
<pre><code>gitlab.vecskill.ovec {
    tls internal
    reverse_proxy gitlab:80
}

portainer.vecskill.ovec {
    tls internal
    # Portainer ทำงานบน HTTPS ภายใน เราจึงต้องตั้งค่า transport เพิ่มเติม
    reverse_proxy portainer:9443 {
        transport http {
            tls_insecure_skip_verify
        }
    }
}
</code></pre>

<h3>ขั้นตอนที่ 1.3: ติดตั้ง Caddy ผ่าน Portainer Stack</h3>
<p>ไปที่ <strong>Stacks > + Add stack</strong>, ตั้งชื่อ (เช่น `caddy-stack`) แล้วใช้โค้ด Docker Compose นี้:</p>
<pre><code>services:
  caddy:
    image: caddy:latest
    container_name: caddy
    restart: always
    ports:
      - "443:443"
      - "443:443/udp"  # สำหรับ HTTP/3
    volumes:
      - /opt/caddy/Caddyfile:/etc/caddy/Caddyfile
      - caddy_data:/data
      - caddy_config:/config
    networks:
      - app_net

networks:
  app_net:
    external: true

volumes:
  caddy_data:
  caddy_config:
</code></pre>
<p>คลิก <strong>Deploy the stack</strong> แล้วรอสักครู่ Caddy จะสร้าง Internal Root CA ของตัวเองขึ้นมาและเริ่มออก Certificate ให้ทุกโดเมนในคอนฟิกทันที</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ก่อนเข้าใช้งานจริง:</strong> เบราว์เซอร์ของเครื่อง Client จะยังไม่เชื่อถือ Certificate นี้ (ขึ้นเตือนสีแดง) จนกว่าจะติดตั้ง Root CA ของ Caddy ลงในเครื่อง Client ก่อน รวมถึงตัวอย่าง Caddyfile
  แบบเต็มสำหรับทุก Service ในคู่มือนี้ (Registry, DNS, Verdaccio, Devpi, Apt-Cacher-Ng) ดูขั้นตอนทั้งหมดต่อได้ที่บท
  <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a>
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2 (ทางเลือกเสริม): เปิดให้เข้าถึงจากอินเทอร์เน็ตจริงด้วย Public Domain + Let's Encrypt</h2>
<p>
  ใช้วิธีนี้เฉพาะกรณีที่ต้องการให้คนนอกทีม (เช่น กรรมการที่ไม่ได้อยู่ในวง Intranet เดียวกัน) เข้าถึง Service ผ่านอินเทอร์เน็ตจริงได้ สำหรับการใช้งานภายในทีมตามปกติ ใช้วิธี Intranet ในส่วนที่ 1
  ก็เพียงพอแล้ว ไม่จำเป็นต้องทำส่วนนี้
</p>

<h3>ขั้นตอนที่ 2.1: เตรียมความพร้อม (สำคัญมาก)</h3>
<ol>
  <li>
    <strong>ตั้งค่า DNS:</strong> ตรวจสอบให้แน่ใจว่าคุณได้สร้าง A Record สำหรับ <strong>ทุกโดเมน</strong> ที่ต้องการใช้งาน ชี้มายัง <strong>Public IP Address</strong> ของ Server คุณ ตัวอย่างเช่น:
    <pre><code># ในระบบ DNS ของ vecskill.ovec
Host: gitlab    -> Points to: [YOUR_PUBLIC_IP]
Host: portainer -> Points to: [YOUR_PUBLIC_IP]</code></pre>
  </li>
  <li>
    <strong>เปิด Firewall:</strong> ตรวจสอบว่า Firewall (UFW) ของคุณได้เปิด Port 80 และ 443 ไว้แล้ว
    <pre><code>sudo ufw allow 80/tcp
sudo ufw allow 443/tcp</code></pre>
  </li>
</ol>

<h3>ขั้นตอนที่ 2.2: ปรับ `Caddyfile` ให้ใช้ Let's Encrypt แทน</h3>
<p>แก้ไขไฟล์ <code>/opt/caddy/Caddyfile</code> โดยเอา <code>tls internal</code> ออก (ไม่ใส่ Directive นี้ = Caddy จะขอ Certificate จาก Let's Encrypt โดยอัตโนมัติแทน):</p>
<pre><code>gitlab.vecskill.ovec {
    reverse_proxy gitlab:80
}

portainer.vecskill.ovec {
    reverse_proxy portainer:9443 {
        transport http {
            tls_insecure_skip_verify
        }
    }
}
</code></pre>
<p>
  และแก้ไข Stack ของ Caddy ให้เปิด Port 80 กลับมาด้วย (เพิ่ม <code>- "80:80"</code> ใน <code>ports</code>) เพราะ Let's Encrypt ต้องใช้ Port 80 ในการยืนยันความเป็นเจ้าของโดเมน
  บันทึกแล้ว Caddy จะขอ Certificate ให้อัตโนมัติ รอสักครู่ (ประมาณ 1-2 นาที) แล้วลองเข้าเว็บ <code>https://gitlab.vecskill.ovec</code> จากเครื่องนอกวง Intranet ก็จะเข้าได้ปกติ
</p>
