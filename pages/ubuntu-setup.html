<h1>การตั้งค่าพื้นฐานสำหรับ Ubuntu Server</h1>
<p>
  ก่อนที่เราจะเริ่มติดตั้งเครื่องมือ DevOps ต่างๆ เช่น Docker, GitLab, และอื่นๆ การเตรียมความพร้อมของ Server ให้มีสถานะที่ "สะอาด", "ปลอดภัย", และ "พร้อมใช้งาน" ถือเป็นขั้นตอนที่สำคัญที่สุด
  หน้านี้จะแนะนำขั้นตอนพื้นฐานที่ควรทำกับ Ubuntu Server ที่เพิ่งติดตั้งใหม่ทุกครั้ง
</p>
<p>
  โดยตัวอย่างนี้จะใช้ <strong>Ubuntu Server 24.04 LTS</strong> ซึ่งเป็นเวอร์ชันล่าสุดในขณะที่เขียนคู่มือนี้ และจะใช้ชื่อเครื่องว่า <strong><code>devops-server</code> </strong> และ Domain name ชื่อว่า
  <strong>vecskill.ovec</strong> พร้อม IP Address แบบ Static เป็น <code>172.16.3.101</code> สำหรับขาฝั่ง WAN และ <code>10.20.30.101</code> สำหรับขาฝั่ง LAN
  (ปรับเปลี่ยนตามความเหมาะสมกับเครือข่ายของคุณ)
</p>

<h2>1. สเปคเครื่องที่แนะนำ (Server Specifications)</h2>
<p>เพื่อให้ทุก Service ที่เราจะติดตั้งทำงานได้อย่างราบรื่น โดยเฉพาะ GitLab ซึ่งใช้ทรัพยากรค่อนข้างสูง Server ของคุณควรมีสเปคขั้นต่ำและสเปคที่แนะนำดังนี้:</p>
<table style="width: 100%; border-collapse: collapse">
  <tr style="background-color: #f2f2f2">
    <th style="padding: 8px; border: 1px solid #ddd; text-align: left">ทรัพยากร</th>
    <th style="padding: 8px; border: 1px solid #ddd; text-align: left">ขั้นต่ำ (พอใช้งานได้)</th>
    <th style="padding: 8px; border: 1px solid #ddd; text-align: left">แนะนำ (สำหรับใช้งานจริง)</th>
  </tr>
  <tr>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>CPU</strong></td>
    <td style="padding: 8px; border: 1px solid #ddd">2 Cores</td>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>4 Cores ขึ้นไป</strong></td>
  </tr>
  <tr>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>RAM</strong></td>
    <td style="padding: 8px; border: 1px solid #ddd">4 GB</td>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>8 GB - 16 GB ขึ้นไป</strong></td>
  </tr>
  <tr>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>Storage</strong></td>
    <td style="padding: 8px; border: 1px solid #ddd">50 GB (SSD)</td>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>100 GB+ (แนะนำ NVMe/SSD)</strong></td>
  </tr>
  <tr>
    <td style="padding: 8px; border: 1px solid #ddd"><strong>OS</strong></td>
    <td colspan="2" style="padding: 8px; border: 1px solid #ddd">Ubuntu Server 24.04 LTS (หรือเวอร์ชัน LTS ล่าสุด)</td>
  </tr>
</table>
<p style="margin-top: 10px"><em>*LTS (Long-Term Support) คือเวอร์ชันที่ได้รับการสนับสนุนด้านความปลอดภัยในระยะยาว ทำให้เหมาะกับงาน Server</em></p>

<h2>2. อัปเดตและอัปเกรดระบบ</h2>
<p>
  หลังจากล็อกอินเข้า Server ใหม่ครั้งแรก สิ่งแรกที่ควรทำเสมอคือการอัปเดตรายการแพ็คเกจและอัปเกรดซอฟต์แวร์ทั้งหมดให้เป็นเวอร์ชันล่าสุด<br />
  # apt update คือการอัปเดต "รายการ" แพ็คเกจทั้งหมด<br />
  # apt upgrade -y คือการ "ติดตั้ง" การอัปเดตเหล่านั้น (-y คือตอบ yes อัตโนมัติ)
</p>
<pre><code>sudo apt update && sudo apt upgrade -y</code></pre>

<h2>3. ตั้งชื่อเครื่อง (Hostname)</h2>
<p>
  การตั้งชื่อเครื่องให้สื่อความหมายจะช่วยให้จัดการได้ง่ายขึ้นในอนาคต<br />
  # เปลี่ยน "devops-server" เป็นชื่อที่คุณต้องการ<br />
  # แก้ไขไฟล์ hosts เพื่อให้ระบบรู้จักชื่อใหม่นี้
</p>
<pre><code>sudo hostnamectl set-hostname devops-server
sudo nano /etc/hosts</code></pre>
<p>ในไฟล์ `hosts` ให้แก้ไขบรรทัด `127.0.1.1` ให้เป็นชื่อใหม่ของคุณ:</p>
<pre><code>127.0.0.1   localhost
127.0.1.1   devops-server</code></pre>
<p>ในกรณีที่ใช้ในระบบเครือข่าย ต้องแก้ไขที่ DNS ให้ชี้มาที่ IP Address ของเครื่อง หรือแก้ไขไฟล์ hosts ของระบบปฏิบัติการ เพื่อเรียกเป็นชื่อได้</p>
<h2>4. ตั้งค่า Static IP Address (ด้วย Netplan)</h2>
<p>
  โดยปกติ Server ควรมี IP Address แบบคงที่ (Static) เพื่อให้ Service และผู้ใช้งานสามารถเชื่อมต่อได้อย่างสม่ำเสมอ การตั้งค่า Network บน Ubuntu Server เวอร์ชันใหม่ๆ จะทำผ่านเครื่องมือที่ชื่อว่า Netplan
  ซึ่งใช้ไฟล์ YAML ในการตั้งค่า
</p>
<ol>
  <li>
    <strong>ค้นหาไฟล์คอนฟิก Netplan:</strong><br />
    ไฟล์คอนฟิกจะอยู่ใน <code>/etc/netplan/</code> และมักจะมีชื่อลงท้ายด้วย <code>.yaml</code>
    <pre><code>ls /etc/netplan/</code></pre>
    คุณอาจจะเจอไฟล์ชื่อ <code>00-installer-config.yaml</code> หรือ <code>50-cloud-init.yaml</code> ให้ใช้ชื่อไฟล์ที่คุณพบในขั้นตอนต่อไป
  </li>
  <li>
    <strong>สำรองไฟล์คอนฟิกเดิม (สำคัญมาก):</strong><br />
    (แทนที่ <code>50-cloud-init.yaml</code> ด้วยชื่อไฟล์ของคุณ)
    <pre><code>sudo cp /etc/netplan/50-cloud-init.yaml /etc/netplan/50-cloud-init.yaml.bak</code></pre>
  </li>
  <li>
    <strong>แก้ไขไฟล์คอนฟิก:</strong>
    <pre><code>sudo nano /etc/netplan/50-cloud-init.yaml</code></pre>
    คุณจะเห็นคอนฟิกเดิมที่อาจจะตั้งค่าเป็น DHCP (<code>dhcp4: true</code>)
    <p><strong>ตัวอย่างคอนฟิกเดิม (DHCP):</strong></p>
    <pre><code>network:
  ethernets:
    ens33: # <-- นี่คือชื่อ Network Interface ของคุณ อาจจะเป็น eth0, ens18, etc.
      dhcp4: true
  version: 2</code></pre>
    <p><strong>ให้แก้ไขเป็นคอนฟิกใหม่ (Static IP) ตามข้อมูลที่คุณให้มา:</strong> (<strong>สำคัญ:</strong> ต้องรักษาระยะย่อหน้า (indentation) ของไฟล์ YAML ให้ถูกต้อง)</p>
    <pre><code>network:
  ethernets:
    ens33: # <-- สำคัญ: ใช้ชื่อ Interface เดิมที่คุณเห็นในไฟล์ ซึ่งจะใช้สำหรับขา WAN
      dhcp4: no
      addresses:
        - 172.16.3.101/24
      gateway4: 172.16.3.1
      nameservers:
        addresses: [172.16.3.101, 8.8.8.8, 1.1.1.1]
        
    ens37: # <-- ถ้ามี Interface ที่สองสำหรับ LAN
            dhcp4: no
            addresses:
              - 10.20.30.101/25
  version: 2
 </code></pre>
  </li>
  <li>
    <strong>นำการตั้งค่าไปใช้งาน (Apply):</strong><br />
    ใช้คำสั่งนี้เพื่อให้ Netplan อ่านไฟล์คอนฟิกใหม่และปรับปรุงระบบ Network
    <pre><code>sudo netplan apply</code></pre>
    <p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
      <strong>ข้อควรระวัง:</strong> หลังจากรันคำสั่งนี้ การเชื่อมต่อ SSH ของคุณอาจจะตัดขาด หากคุณเชื่อมต่อผ่าน IP เดิม คุณจะต้องทำการเชื่อมต่อ SSH ใหม่อีกครั้งโดยใช้ IP Address ใหม่คือ
      <code>172.16.3.101</code>
    </p>
  </li>
  <li>
    <strong>ตรวจสอบ IP Address ใหม่:</strong><br />
    หลังจากเชื่อมต่อใหม่แล้ว สามารถใช้คำสั่ง <code>ip a</code> เพื่อตรวจสอบว่า Interface ของคุณได้รับ IP ที่ถูกต้องแล้วหรือไม่
  </li>
</ol>
<h2>5. การจัดการผู้ใช้งานและความปลอดภัย SSH</h2>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>คำเตือนด้านความปลอดภัย:</strong> การล็อกอินด้วย User `root` โดยตรงผ่าน SSH เป็นความเสี่ยงสูงมาก เพราะเป็นเป้าหมายแรกของการโจมตีแบบ Brute-force ดังนั้น Best Practice คือการสร้าง User
  ใหม่ที่มีสิทธิ์ `sudo` และปิดการล็อกอินของ `root`
</p>
<ol>
  <li>
    <strong>สร้าง User ใหม่:</strong> (แทนที่ `devops` ด้วยชื่อที่คุณต้องการ)
    <pre><code>sudo adduser devops</code></pre>
    ระบบจะให้คุณตั้งรหัสผ่านและกรอกข้อมูลเล็กน้อย
  </li>
  <li>
    <strong>เพิ่ม User เข้าไปในกลุ่ม `sudo`:</strong><br />
    บน Ubuntu กลุ่มที่มีสิทธิ์เทียบเท่า `root` คือกลุ่ม `sudo` (ไม่ใช่ `wheel` เหมือนบน CentOS)
    <pre><code>sudo usermod -aG sudo devops</code></pre>
  </li>
  <li>
    <strong>(แนะนำอย่างยิ่ง) คัดลอก SSH Key ไปยัง User ใหม่:</strong><br />
    เพื่อการล็อกอินที่ปลอดภัยและไม่ต้องใช้รหัสผ่าน ให้รันคำสั่งนี้จาก "เครื่องคอมพิวเตอร์ของคุณ"
    <pre><code>ssh-copy-id devops@[IP_ADDRESS_SERVER_ของคุณ]</code></pre>
  </li>
  <li>
    <strong>ปิดการล็อกอินด้วย `root` ผ่าน SSH:</strong><br />
    หลังจากที่คุณสามารถล็อกอินด้วย User ใหม่ (`ssh devops@...`) ได้แล้ว ให้ทำการปิดการล็อกอินของ `root`
    <pre><code>sudo nano /etc/ssh/sshd_config</code></pre>
    หาบรรทัดที่เขียนว่า `PermitRootLogin` และเปลี่ยนค่าเป็น `no`:
    <pre><code>PermitRootLogin no</code></pre>
    จากนั้นรีสตาร์ท service SSH เพื่อให้การตั้งค่ามีผล:
    <pre><code>sudo systemctl restart sshd</code></pre>
  </li>
</ol>

<h2>6. (ทางเลือก) การใช้ `sudo` โดยไม่ต้องใส่รหัสผ่าน</h2>
<p>สำหรับ Server ที่ใช้ส่วนตัวหรือในเครือข่ายที่เชื่อถือได้ หากคุณต้องการความสะดวกสบายในการรันคำสั่ง `sudo` โดยไม่ต้องกรอกรหัสผ่านทุกครั้ง สามารถทำได้ดังนี้</p>
<p><strong>คำเตือน:</strong> การทำเช่นนี้จะลดระดับความปลอดภัยลงเล็กน้อย ควรทำเมื่อเข้าใจความเสี่ยง<br /># คำสั่ง visudo เป็นวิธีที่ปลอดภัยในการแก้ไขไฟล์ sudoers</p>
<pre><code>sudo visudo</code></pre>
<p>จากนั้นเลื่อนไปที่ท้ายไฟล์และเพิ่มบรรทัดนี้เข้าไป (แทนที่ `devops` ด้วยชื่อของคุณ):</p>
<pre><code>devops ALL=(ALL) NOPASSWD: ALL</code></pre>
<p>กด `Ctrl + X` > `Y` > `Enter` ในกรณีที่เป็น nano Editor หรือ กด Esc ตามด้วย :wq ในกรณีที่เป็น vi Editor เพื่อบันทึกและออก</p>

<h2>7. ตั้งค่า Firewall พื้นฐาน (UFW)</h2>
<p>
  UFW (Uncomplicated Firewall) เป็นเครื่องมือจัดการ Firewall ที่ใช้งานง่ายและควรเปิดใช้งานเสมอ
  <br />อนุญาตการเชื่อมต่อที่จำเป็น
</p>
<pre><code>sudo ufw allow OpenSSH     # Port 22 สำหรับ SSH
sudo ufw allow 80/tcp       # Port 80 สำหรับ HTTP
sudo ufw allow 443/tcp      # Port 443 สำหรับ HTTPS
</code></pre>
<p><br />อนุญาต Port ที่จะใช้ในโปรเจกต์ของเรา</p>

<pre><code>sudo ufw allow 2222/tcp     # สำหรับ GitLab SSH
sudo ufw allow 5000/tcp     # สำหรับ Docker Registry
sudo ufw allow 8080/tcp     # สำหรับ Registry UI
sudo ufw allow 9443/tcp     # สำหรับ Portainer
</code></pre>
<p>เปิดใช้งาน Firewall และ ตรวจสอบสถานะของ UFW</p>

<pre><code>sudo ufw enable
sudo ufw status</code></pre>
<p>
  เมื่อทำตามขั้นตอนทั้งหมดนี้แล้ว Server ของคุณก็มีความพร้อมทั้งด้านความปลอดภัยและประสิทธิภาพ พร้อมสำหรับบทต่อไปคือ
  <a href="#" onclick="document.querySelector('a[data-page=\'docker\']').click();">ติดตั้ง Docker</a> แล้วครับ!
</p>
<div class="image-container">
  <img src="images/00-ssh-dos.png" alt="Docker Hello World Output" class="content-image" />
  <p><em>หน้าผลลัพธ์การ Remote เข้าไปยัง Server ผ่าน Command Shell</em></p>
</div>
