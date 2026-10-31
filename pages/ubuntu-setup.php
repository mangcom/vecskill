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
    <strong>สร้าง User หลัก:</strong> (แทนที่ `devops` ด้วยชื่อที่คุณต้องการ)
    <pre><code>sudo adduser devops</code></pre>
    ระบบจะให้คุณตั้งรหัสผ่านและกรอกข้อมูลเล็กน้อย
  </li>
  <li>
    <strong>เพิ่ม User เข้าไปในกลุ่ม `sudo`:</strong><br />
    บน Ubuntu กลุ่มที่มีสิทธิ์เทียบเท่า `root` คือกลุ่ม `sudo` (ไม่ใช่ `wheel` เหมือนบน CentOS)
    <pre><code>sudo usermod -aG sudo devops</code></pre>
  </li>
  <li>
    <strong>สร้าง User ที่สอง:</strong><br />
    การแข่งขันกำหนดให้ต้องมี User ที่ Login เข้าใช้งานได้อย่างน้อย <strong>2 User</strong> ตามที่คณะกรรมการกำหนด ให้ทำซ้ำขั้นตอนเดิม (แทนที่ `devops2` ด้วยชื่อที่ต้องการ):
    <pre><code>sudo adduser devops2
sudo usermod -aG sudo devops2</code></pre>
  </li>
</ol>

<h3>5.1 เชื่อมต่อแบบไม่ใช้รหัสผ่านด้วย SSH Key</h3>
<p>
  เพื่อความปลอดภัยและความสะดวก ควร Login ด้วย SSH Key แทนรหัสผ่าน โดยสร้างคู่กุญแจ (Key Pair) ไว้ที่ "เครื่องคอมพิวเตอร์ของคุณ" (Client) แล้วนำเฉพาะ Public Key ไปวางไว้ที่ Server เท่านั้น
  Private Key จะไม่ถูกส่งออกจากเครื่องของคุณเลย
</p>

<style>
.tab { overflow: hidden; border-bottom: 1px solid #ccc; background-color: #f1f1f1; border-radius: 5px 5px 0 0;}
.tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; font-size: 17px; font-family: 'Sarabun', sans-serif;}
.tab button:hover { background-color: #ddd; }
.tab button.active { background-color: #ccc; font-weight: bold;}
.tabcontent { display: none; padding: 20px 12px; border: 1px solid #ccc; border-top: none; border-radius: 0 0 5px 5px; background-color: white;}
</style>
<div class="tab">
  <button class="tablinks active" data-target-tab="SSHWindows">จากเครื่อง Windows</button>
  <button class="tablinks" data-target-tab="SSHLinuxMac">จากเครื่อง Linux / macOS</button>
</div>

<div id="SSHLinuxMac" class="tabcontent">
  <p>บน Linux และ macOS มีคำสั่ง <code>ssh-copy-id</code> ให้ใช้งานได้ทันที ซึ่งจะจัดการสร้างโฟลเดอร์ <code>~/.ssh</code> และสิทธิ์ไฟล์บน Server ให้ถูกต้องโดยอัตโนมัติ</p>
  <ol>
    <li>
      <strong>สร้าง Key Pair (ถ้ายังไม่มี):</strong>
      <pre><code>ssh-keygen -t ed25519 -C "your_email@example.com"</code></pre>
      กด Enter ผ่านได้ทุกช่อง (ไม่ใส่ Passphrase ก็ได้ ถ้าต้องการความสะดวก)
    </li>
    <li>
      <strong>คัดลอก Public Key ไปยัง Server:</strong>
      <pre><code>ssh-copy-id devops@[IP_ADDRESS_SERVER_ของคุณ]</code></pre>
    </li>
    <li>
      <strong>ทดสอบ Login:</strong> ควร Login ได้โดยไม่ถามรหัสผ่านอีกต่อไป
      <pre><code>ssh devops@[IP_ADDRESS_SERVER_ของคุณ]</code></pre>
    </li>
  </ol>
</div>

<div id="SSHWindows" class="tabcontent" style="display: block">
  <p>
    Windows 10/11 มี OpenSSH Client ติดตั้งมาให้แล้วโดยปกติ (ใช้งานผ่าน PowerShell หรือ Command Prompt) แต่ <strong>ไม่มีคำสั่ง <code>ssh-copy-id</code></strong> มาให้ ต้องคัดลอก Public Key
    ไปวางเองตามขั้นตอนนี้
  </p>
  <ol>
    <li>
      <strong>เปิด PowerShell แล้วสร้าง Key Pair (ถ้ายังไม่มี):</strong>
      <pre><code class="language-powershell">ssh-keygen -t ed25519 -C "your_email@example.com"</code></pre>
      กด Enter ผ่านได้ทุกช่อง ไฟล์ที่ได้จะอยู่ที่ <code>C:\Users\&lt;ชื่อคุณ&gt;\.ssh\id_ed25519</code> (Private) และ <code>id_ed25519.pub</code> (Public)
    </li>
    <li>
      <strong>ส่ง Public Key ไปต่อท้ายไฟล์ <code>authorized_keys</code> บน Server โดยตรงผ่าน SSH:</strong><br />
      คำสั่งนี้จะยังถามรหัสผ่านของ <code>devops</code> อยู่ 1 ครั้งสุดท้าย (เพราะยังไม่มี Key ให้ใช้) หลังจากนี้จะไม่ถามอีก
      <pre><code class="language-powershell">type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh devops@[IP_ADDRESS_SERVER_ของคุณ] "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"</code></pre>
    </li>
    <li>
      <strong>(ถ้าคำสั่งด้านบนใช้ไม่ได้) วิธี Manual:</strong>
      <ol>
        <li>
          เปิดไฟล์ Public Key ด้วย Notepad เพื่อคัดลอกเนื้อหา:
          <pre><code class="language-powershell">notepad $env:USERPROFILE\.ssh\id_ed25519.pub</code></pre>
        </li>
        <li>SSH เข้า Server ด้วยรหัสผ่านตามปกติ แล้ววางเนื้อหานั้นต่อท้ายไฟล์ <code>~/.ssh/authorized_keys</code> (สร้างไฟล์/โฟลเดอร์ใหม่ถ้ายังไม่มี)</li>
      </ol>
    </li>
    <li>
      <strong>ทดสอบ Login:</strong> ควร Login ได้โดยไม่ถามรหัสผ่านอีกต่อไป
      <pre><code class="language-powershell">ssh devops@[IP_ADDRESS_SERVER_ของคุณ]</code></pre>
    </li>
  </ol>
</div>

<h3>5.2 ปิดการล็อกอินด้วย `root` ผ่าน SSH</h3>
<p>
  หลังจากที่คุณสามารถ Login ด้วย User ใหม่ (`ssh devops@...`) ด้วย SSH Key ได้แล้ว <strong>(สำคัญ: ต้องทดสอบ Login ด้วย User ใหม่ให้สำเร็จก่อน)</strong> ให้ทำการปิดการล็อกอินของ `root`
  เพื่อป้องกันการโจมตีแบบ Brute-force ที่เป้าหมายแรกมักจะเป็น Account `root` เสมอ
</p>
<pre><code>sudo nano /etc/ssh/sshd_config</code></pre>
<p>หาบรรทัดที่เขียนว่า `PermitRootLogin` และเปลี่ยนค่าเป็น `no`:</p>
<pre><code>PermitRootLogin no</code></pre>
<p>ยังไม่ต้อง Restart service ตอนนี้ ให้ทำขั้นตอนถัดไป (เปลี่ยน Port SSH) ในไฟล์เดียวกันนี้ให้เสร็จก่อน แล้วค่อย Restart พร้อมกันทีเดียว</p>

<h3>5.3 เปลี่ยน Port SSH เริ่มต้น</h3>
<p>
  การแข่งขันกำหนดให้ต้องสามารถ Remote เข้าเครื่องผ่าน Port ที่คณะกรรมการกำหนด (ไม่ใช่ Port 22 ที่เป็นค่าเริ่มต้นเสมอไป) ในไฟล์ <code>sshd_config</code> เดียวกันนี้ (ที่ยังเปิดค้างจากขั้นตอนที่แล้ว)
  ให้หาบรรทัด <code>#Port 22</code> แล้วแก้ไข/เพิ่มเป็น Port ที่ต้องการ (ตัวอย่างใช้ <code>2200</code>):
</p>
<pre><code>Port 2200</code></pre>
<p>บันทึกไฟล์ (`Ctrl+X` > `Y` > `Enter`) แล้วรีสตาร์ท service SSH เพื่อให้ทั้ง `PermitRootLogin no` และ Port ใหม่มีผลพร้อมกัน:</p>
<pre><code>sudo systemctl restart sshd</code></pre>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ข้อควรระวังสำคัญที่สุด:</strong> <strong>ห้ามปิดหน้าต่าง Terminal ที่เชื่อมต่ออยู่เดิมเด็ดขาด</strong> จนกว่าจะทดสอบเปิด Session ใหม่ด้วย Port และ User ใหม่สำเร็จก่อน
  (เช่น <code>ssh -p 2200 devops@[IP]</code>) เพื่อป้องกันกรณีตั้งค่าผิดแล้วหลุดออกจาก Server ไปโดยเข้าใหม่ไม่ได้อีก (Lockout) ถ้าเข้าไม่ได้ ให้กลับไปแก้ที่หน้าต่างเดิมที่ยังเปิดอยู่ได้ทันที
</p>

<h2>6. การใช้ `sudo` โดยไม่ต้องใส่รหัสผ่าน (ข้อกำหนดของการแข่งขัน)</h2>
<p>
  เกณฑ์การแข่งขันกำหนดให้ User ที่ Login เข้ามาต้องสามารถใช้คำสั่ง <code>sudo</code> ได้โดยไม่ต้องยืนยันรหัสผ่าน จึงเป็นขั้นตอนที่ <strong>ต้องทำ</strong> ไม่ใช่ทางเลือก
</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ทำความเข้าใจความเสี่ยง:</strong> การตั้งค่านี้ทำให้ User คนนั้นมีสิทธิ์เทียบเท่า `root` ได้ทันทีโดยไม่ต้องยืนยันตัวตนซ้ำ จึงควรทำคู่กับการปิด Root Login (ข้อ 5.2) และการใช้ SSH Key แทนรหัสผ่าน
  (ข้อ 5.1) เสมอ เพื่อให้การเข้าถึง Server ยังต้องพิสูจน์ตัวตนด้วย Key ที่เก็บไว้เฉพาะเครื่องของคุณอยู่ดี<br />
  # คำสั่ง visudo เป็นวิธีที่ปลอดภัยในการแก้ไขไฟล์ sudoers
</p>
<pre><code>sudo visudo</code></pre>
<p>จากนั้นเลื่อนไปที่ท้ายไฟล์และเพิ่มบรรทัดนี้เข้าไป (ทำกับทุก User ที่สร้างไว้ แทนที่ `devops` ด้วยชื่อของคุณ):</p>
<pre><code>devops ALL=(ALL) NOPASSWD: ALL</code></pre>
<p>กด `Ctrl + X` > `Y` > `Enter` ในกรณีที่เป็น nano Editor หรือ กด Esc ตามด้วย :wq ในกรณีที่เป็น vi Editor เพื่อบันทึกและออก</p>

<h2>7. ตั้งค่า Firewall พื้นฐาน (UFW)</h2>
<p>
  UFW (Uncomplicated Firewall) เป็นเครื่องมือจัดการ Firewall ที่ใช้งานง่ายและควรเปิดใช้งานเสมอ
  <br />อนุญาตการเชื่อมต่อที่จำเป็น (แทนที่ <code>2200</code> ด้วย Port SSH ที่คุณตั้งไว้ในข้อ 5.3)
</p>
<pre><code>sudo ufw allow 2200/tcp     # Port SSH ใหม่ที่ตั้งไว้ (แทนที่ OpenSSH/Port 22 เดิม)
sudo ufw allow 80/tcp       # Port 80 สำหรับ HTTP
sudo ufw allow 443/tcp      # Port 443 สำหรับ HTTPS
</code></pre>
<p><br />อนุญาต Port ที่จะใช้ในโปรเจกต์ของเรา</p>

<pre><code>sudo ufw allow 2222/tcp     # สำหรับ GitLab SSH
sudo ufw allow 5000/tcp     # สำหรับ Docker Registry
sudo ufw allow 8080/tcp     # สำหรับ Registry UI
sudo ufw allow 81/tcp       # สำหรับ Nginx Proxy Manager Admin UI (ถ้าเลือกใช้ NPM แทน Caddy)
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
