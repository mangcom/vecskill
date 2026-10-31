<h1>การตั้งค่า DNS Server ด้วย Technitium</h1>
<p>
  Technitium DNS Server เป็น DNS Server แบบ Open Source ที่มีความสามารถหลากหลาย ช่วยให้เราสามารถสร้างระบบ DNS ส่วนตัวภายในเครือข่ายของเราได้ ทำให้เราสามารถเข้าถึง Service ต่างๆ (เช่น Registry, GitLab,
  Portainer) ด้วยชื่อ Domain Name ที่เรากำหนดเอง (เช่น <code>gitlab.vecskill.ovec</code>) แทนที่จะต้องจำ IP Address
</p>
<p>ในคู่มือนี้ เราจะติดตั้ง Technitium DNS Server โดยใช้ Docker และ Portainer</p>

<h3>สิ่งที่ต้องเตรียมก่อน</h3>
<ul>
  <li>ติดตั้ง Docker และ Portainer เรียบร้อยแล้ว</li>
  <li>สร้าง Docker Network ชื่อ <code>app_net</code> ไว้แล้ว</li>
  <li>ทราบ IP Address แบบ Static ของเครื่อง Server (ในตัวอย่างนี้คือ <code>172.16.3.101</code>)</li>
</ul>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 1: ปรับแต่ง DNS ของเครื่อง Server (Host OS)</h2>
<p>
  โดยปกติ Ubuntu Server จะมี Service ชื่อ `systemd-resolved` ทำหน้าที่จัดการ DNS และอาจจะใช้งาน Port 53 ซึ่งเป็น Port มาตรฐานของ DNS อยู่แล้ว เราต้องปิดการทำงานส่วนนี้เพื่อให้ Technitium DNS สามารถใช้
  Port 53 ได้ และตั้งค่าให้ Server ชี้ DNS มาที่ตัวเองเป็นอันดับแรก
</p>
<ol>
  <li>
    <strong>ปิด DNSStubListener:</strong><br />
    แก้ไขไฟล์คอนฟิกของ `systemd-resolved` เพื่อไม่ให้มันดักฟัง (Listen) ที่ Port 53
    <pre><code>sudo sed -i 's/^#\?DNSStubListener=.*/DNSStubListener=no/' /etc/systemd/resolved.conf</code></pre>
  </li>
  <li>
    <strong>เปลี่ยน Symlink ของ `resolv.conf`:</strong><br />
    เพื่อให้ระบบใช้ไฟล์ `resolv.conf` ที่จัดการโดย `systemd-resolved` โดยตรง
    <pre><code>sudo ln -sf /run/systemd/resolve/resolv.conf /etc/resolv.conf</code></pre>
  </li>
  <li>
    <strong>ตั้งค่า Nameserver หลักเป็น IP ของตัวเอง:</strong><br />
    แก้ไขไฟล์ <code>/etc/systemd/resolved.conf</code> อีกครั้ง
    <pre><code>sudo nano /etc/systemd/resolved.conf</code></pre>
    หาบรรทัด `[Resolve]` (ถ้าไม่มี ให้เพิ่มเข้าไป) และเพิ่ม/แก้ไขส่วน `DNS=` และ `FallbackDNS=` ให้มีลักษณะดังนี้ (ใส่ IP ของ Server เป็นลำดับแรกสุด):
    <pre><code>[Resolve]
DNS=172.16.3.101 10.97.4.1 8.8.8.8 1.1.1.1
# FallbackDNS= (ถ้ามีบรรทัดนี้ ใส่ # ข้างหน้าเพื่อปิดการใช้งาน หรือลบทิ้ง)
# Domains=
# LLMNR=no
# MulticastDNS=no
# DNSSEC=no
# DNSOverTLS=no
# Cache=no
DNSStubListener=no</code></pre>
    <p>จากนั้นกด `Ctrl+X` > `Y` > `Enter` เพื่อบันทึก</p>
  </li>
  <li>
    <strong>รีสตาร์ท Service:</strong>
    <pre><code>sudo systemctl restart systemd-resolved</code></pre>
  </li>
  <li>
    <strong>ตรวจสอบผลลัพธ์:</strong> ดูเนื้อหาไฟล์ `/etc/resolv.conf` ควรจะเห็น IP ของ Server เป็น nameserver แรก
    <pre><code>cat /etc/resolv.conf</code></pre>
    <pre><code>nameserver 172.16.3.101
nameserver 10.97.4.1
nameserver 8.8.8.8
search .</code></pre>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 2: สร้าง Docker Volume</h2>
<p>สร้าง Volume สำหรับเก็บข้อมูลการตั้งค่าของ Technitium DNS Server เพื่อให้ข้อมูลไม่หายไปเมื่อ Container ถูกสร้างใหม่</p>
<pre><code>docker volume create dns_config</code></pre>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 3: ติดตั้งผ่าน Portainer Stack</h2>
<p>ไปที่ Portainer > Stacks > + Add stack, ตั้งชื่อ (เช่น `technitium-stack`) แล้วใช้โค้ด Docker Compose นี้:</p>
<pre><code>networks:
  app_net:
    external: true

volumes:
  dns_config:
    external: true

services:
  technitium-dns:
    image: technitium/dns-server:latest
    container_name: technitium-dns
    hostname: dns-server # ตั้งชื่อ Hostname ให้ Container (เผื่อใช้อ้างอิง)
    restart: unless-stopped
    ports:
      - "53:53/udp"      # DNS UDP
      - "53:53/tcp"      # DNS TCP
      - "5380:5380/tcp"  # Web Admin UI (HTTP)
    volumes:
      - dns_config:/etc/dns
    networks:
      - app_net
    cap_add:
      - NET_ADMIN # จำเป็นเพื่อให้ DNS Server ทำงานได้เต็มประสิทธิภาพ
</code></pre>
<p>จากนั้นคลิก <strong>Deploy the stack</strong></p>
<h2>กรณีที่ต้องการติดตั้งบน Host โดยไม่ใช้ Docker</h2>
<p>สามารถใช้การติดตั้ง ด้วยคำสั่ง ด้านล่าง แล้วเข้าสู่หน้า Web UI และตั้งค่าเริ่มต้น ผ่าน Port 5380 ตามขั้นตอนที่ 4</p>
<pre><code>curl -sSL https://download.technitium.com/dns/install.sh | sudo bash
</code></pre>
<h3>คำสั่งจัดการ Service ทั่วไป หากติดตั้งใน Host ที่ไม่ใช่ Docker</h3>
<ul>
  <li>หยุดการทำงาน: <code>sudo systemctl stop dns</code></li>
  <li>เริ่มการทำงาน: <code>sudo systemctl start dns</code></li>
  <li>รีสตาร์ท: <code>sudo systemctl restart dns</code></li>
</ul>
<h3>การอัปเดตในอนาคต</h3>
<p>เมื่อมีเวอร์ชันใหม่ สามารถเข้าหน้า Web UI และกดอัปเดตผ่านปุ่ม Update บนหน้าเว็บได้เลย</p>
<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 4: การตั้งค่าเริ่มต้นผ่าน Web UI</h2>
<ol>
  <li>
    <strong>เข้าสู่หน้า Admin UI:</strong><br />
    เปิดเว็บเบราว์เซอร์แล้วไปที่ <code>http://[IP_SERVER_ของคุณ]:5380</code> (เช่น <code>http://172.16.3.101:5380</code>)
  </li>
  <li>
    <strong>ตั้งรหัสผ่าน Admin:</strong><br />
    ในการเข้าใช้งานครั้งแรก ระบบจะบังคับให้คุณตั้งรหัสผ่านสำหรับผู้ดูแลระบบ ให้กรอกรหัสผ่านที่คาดเดายาก 2 ครั้ง แล้วคลิก "Save"
    <div class="image-container">
      <img src="images/07-01-create.png" alt="" class="content-image" />
      <p>หน้าจอสำหรับตั้งรหัสผ่าน Admin ครั้งแรก</p>
    </div>
  </li>
  <li>
    <strong>ล็อกอินเข้าสู่ระบบ:</strong><br />
    ใช้รหัสผ่านที่คุณเพิ่งตั้งเพื่อล็อกอินเข้าสู่หน้า Dashboard หลัก
    <div class="image-container">
      <img src="images/07-02-dashboard.png" alt="" class="content-image" />
      <p>หน้า Dashboard หลักของ Technitium DNS Server</p>
    </div>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 5: การเพิ่ม DNS Zone และ Records (ตัวอย่าง)</h2>
<p>
  เพื่อให้ DNS Server ของเราสามารถตอบชื่อโดเมนภายใน (เช่น <code>devops-server.vecskill.ovec</code>) ได้ เราต้องสร้าง "Zone" สำหรับโดเมนนั้นๆ และเพิ่ม "Record" (เช่น A Record) เพื่อชี้ชื่อไปยัง IP
  Address ที่ถูกต้อง
</p>
<ol>
  <li>
    <strong>ไปที่เมนู Zones:</strong><br />
    คลิกที่ แถบเมนู "Zones" ทางด้านขวาของ แถบ "Dashboard" แล้วคลิกที่ปุ่ม "Add Zone" ด้านบนขวา
    <div class="image-container">
      <img src="images/07-03-createzone.png" alt="" class="content-image" />
      <p>หน้าจอการเพิ่ม DNS Zone ใหม่</p>
    </div>
  </li>
  <li>
    <strong>Add Zone Forward:</strong><br />
    คลิกที่ปุ่ม "Add Zone" แล้วกรอกชื่อ Zone ของคุณ เช่น <code>vecskill.ovec</code> แล้วคลิก "Add"
    <div class="image-container">
      <img src="images/07-04-zonefw.png" alt="" class="content-image" />
      <p>หน้าจอการเพิ่ม DNS Zone vecskill.ovec ใหม่</p>
    </div>
  </li>

  <li>
    <strong>Add Zone Reverse (กรณีอยากถามจาก IP):</strong><br />
    คลิกที่ปุ่ม "Add Zone" แล้วกรอกชื่อ Zone Reverse ของคุณ เช่น <code>3.16.172.in-addr.arpa</code> แล้วคลิก "Add" (เนื่องจาก IP Server ของเราคือ <code>172.16.3.101</code>)
    <div class="image-container">
      <img src="images/07-05-zonerev.png" alt="" class="content-image" />
      <p>หน้าจอการเพิ่ม DNS Zone 3.16.172.in-addr.arpa สำหรับทำ Reverse</p>
    </div>
  </li>
  <li>
    <strong>Manage Zone</strong><br />
    เมื่อกลับมาหน้าหลักของแถบ "Zones" คลิกที่ชื่อของ Zone ที่คุณสร้าง
    <div class="image-container">
      <img src="images/07-06-zonemanage.png" alt="" class="content-image" />
      <p>หน้าจอการการเข้าไปจัดการเพื่อเพิ่ม Records</p>
    </div>
  </li>
  <li>
    <strong>การเพิ่ม Record ใน Zone vecskill.ovec</strong><br />
    คลิกที่ปุ่ม Add Record
    <div class="image-container">
      <img src="images/07-07-add_rec.png" alt="" class="content-image" />
      <p>หน้าจอการการเข้าไปจัดการเพื่อเพิ่ม Record</p>
    </div>
  </li>
  <li>
    <strong>Add Record:</strong><br />
    ในหน้าต่าง popup ที่ปรากฏขึ้นมา ให้เลือกประเภทของ Record ที่ต้องการสร้าง (ในตัวอย่างนี้เราจะสร้าง A Record) แล้วกรอกข้อมูลดังนี้:
    <ul>
      <li><strong>Name:</strong> ใส่ชื่อ Host (เช่น <code>devops-server</code>,<code>registry</code>)</li>
      <li><strong>Type:</strong> เลือก <code>A</code></li>
      <li><strong>TTL:</strong> ปล่อยไว้ตามค่าเริ่มต้น (เช่น 3600)</li>
      <li><strong>IP Address:</strong> ใส่ IP Address ของเครื่อง Server ของคุณ (<code>172.16.3.101</code>)</li>
    </ul>
    คลิก "Save" ทำซ้ำสำหรับทุก Service ที่คุณต้องการสร้างชื่อให้
    <div class="image-container">
      <img src="images/07-08-create_a.png" alt="" class="content-image" />
      <p>หน้าจอการเพิ่ม A Record สำหรับชี้ชื่อโดเมนไปยัง devops-server.vecskill.ovec ไปยัง IP Address 172.16.3.101</p>
    </div>
    <div class="image-container">
      <img src="images/07-09-create_b.png" alt="" class="content-image" />
      <p>หน้าจอการเพิ่ม A Record สำหรับชี้ชื่อโดเมนไปยัง registry.vecskill.ovec ไปยัง IP Address 172.16.3.101</p>
    </div>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนที่ 6: ตั้งค่า DNS ที่เครื่อง Client</h2>
<p>
  เพื่อให้เครื่องคอมพิวเตอร์อื่นๆ ในเครือข่ายของคุณสามารถใช้ Technitium DNS Server ในการค้นหาชื่อโดเมนภายในได้ คุณต้องไปตั้งค่า DNS ของเครื่อง Client เหล่านั้นให้ชี้มาที่ IP Address ของ Server
  (<code>172.16.3.101</code>) เป็น DNS Server ลำดับแรก
</p>
<p>วิธีการตั้งค่าจะแตกต่างกันไปตามระบบปฏิบัติการ (Windows, macOS, Linux) หรืออาจจะตั้งค่าที่ Router โดยตรงเพื่อให้แจก DNS นี้ให้กับทุกเครื่องในเครือข่ายผ่าน DHCP ก็ได้</p>

<p>เมื่อตั้งค่าเรียบร้อยแล้ว คุณก็จะสามารถเข้าถึง <code>http://devops-server.vecskill.ovec</code> หรือ <code>http://registry.vecskill.ovec</code> จากเครื่อง Client ของคุณได้โดยตรง! 🎉</p>
