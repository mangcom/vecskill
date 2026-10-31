<h1>การตั้งค่า Apt-Cacher-Ng (APT Cache Server)</h1>
<p>
  Apt-Cacher-Ng คือ Proxy Server ที่ออกแบบมาสำหรับแคช (Cache) ไฟล์แพ็กเกจ Debian/Ubuntu (.deb) โดยเฉพาะ เมื่อเครื่อง Client (เช่น Server อื่นๆ หรือ VM ในเครือข่ายของคุณ) ร้องขอแพ็กเกจผ่าน
  Apt-Cacher-Ng เป็นครั้งแรก มันจะดาวน์โหลดแพ็กเกจนั้นจากอินเทอร์เน็ตมาเก็บไว้ และในครั้งต่อๆ ไปที่มีเครื่องอื่นร้องขอแพ็กเกจเดียวกัน Apt-Cacher-Ng ก็จะส่งไฟล์ที่แคชไว้ให้ทันที โดยไม่ต้องดาวน์โหลดใหม่
</p>
<h3>ประโยชน์</h3>
<ul>
  <li>🚀 **เร่งความเร็ว:** การ `apt update`, `apt upgrade`, `apt install` บนเครื่อง Client จะเร็วขึ้นมาก เพราะดึงไฟล์จาก Cache ในเครือข่าย LAN</li>
  <li>📉 **ประหยัด Bandwidth:** ลดปริมาณการดาวน์โหลดข้อมูลซ้ำๆ จากอินเทอร์เน็ต</li>
  <li>🏢 **เหมาะสำหรับ:** เครือข่ายที่มีเครื่อง Ubuntu/Debian หลายเครื่อง, Homelab, หรือสภาพแวดล้อมที่อินเทอร์เน็ตช้า/จำกัด</li>
</ul>
<p>ในคู่มือนี้ เราจะติดตั้งและตั้งค่า Apt-Cacher-Ng บน Server หลักของเรา (สมมติว่ามี IP: <code>10.20.30.101</code>)</p>

<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
    <strong>บทบาทของหน้านี้ในการแข่งขัน:</strong> Apt-Cacher-Ng เป็นโครงสร้างพื้นฐานที่ <strong>ฝั่งเจ้าภาพ</strong> เตรียมไว้ให้ทุกทีมใช้งานร่วมกัน ไม่ใช่สิ่งที่ผู้เข้าแข่งขันแต่ละทีมต้องติดตั้งเอง
    และไม่ได้อยู่ในเกณฑ์การให้คะแนนโดยตรง (ไม่ปรากฏในตารางคะแนนของเกณฑ์ทักษะ) แต่ "จำเป็นต้องมี" เพราะเกณฑ์การแข่งขันอนุญาตให้ต่ออินเทอร์เน็ตได้เฉพาะช่วงเวลาสั้นๆ ตอนเริ่มตั้งค่าระบบ
    (และหลังจากนั้นต้องขออนุญาตเป็นครั้งๆ ไป) จึงต้องมีตัวช่วยแคชแพ็กเกจไว้ล่วงหน้าเพื่อให้ทุกทีมติดตั้งซอฟต์แวร์ที่ต้องใช้ได้แม้ไม่มีอินเทอร์เน็ตแล้ว
</p>

<h2 style="color: #c0392b;">ข้อควรระวังและข้อจำกัดสำหรับการแข่งขัน</h2>
<ul>
    <li>
        <strong>⏱ ต้อง "อุ่นแคช" ให้ครบก่อนอินเทอร์เน็ตถูกตัด:</strong> เพราะ Apt-Cacher-Ng จะเก็บแคชได้เฉพาะแพ็กเกจที่เคยมีการร้องขอผ่านมันอย่างน้อย 1 ครั้งเท่านั้น หากทีมใดต้องการแพ็กเกจที่ยังไม่เคยถูก Cache
        ไว้ และอินเทอร์เน็ตถูกตัดไปแล้ว คำสั่งนั้นจะล้มเหลวทันที ดังนั้นต้องวางแผนและรันขั้นตอน <strong>1.5 อุ่นแคชล่วงหน้า</strong> ด้านล่างให้ครอบคลุมแพ็กเกจที่คาดว่าทุกทีมจะใช้ <strong>ก่อนหมดเวลา</strong>
        ที่กรรมการอนุญาตให้ต่ออินเทอร์เน็ต
    </li>
    <li>
        <strong>🔓 Cache ได้จริงเฉพาะ HTTP เท่านั้น:</strong> จาก `PassThroughPattern: .*` ด้านล่าง การเชื่อมต่อแบบ HTTPS (เช่น Docker Repository ใน
        <strong>ส่วนที่ 4</strong>) จะถูก "ส่งผ่าน" (Tunnel) แบบที่ Apt-Cacher-Ng มองไม่เห็นเนื้อข้อมูลและ<strong>ไม่ได้ถูกแคชจริง</strong>
        ได้แค่ช่วยให้ Client เชื่อมต่อออกไปได้ผ่าน Proxy เดียวกันเท่านั้น ถ้าอินเทอร์เน็ตถูกตัด แหล่งข้อมูลที่เป็น HTTPS ล้วนๆ (ไม่มี Mirror แบบ HTTP ให้ใช้) จะใช้งานไม่ได้อีกต่อไปแม้เคยเรียกผ่านมาแล้วก็ตาม
        มีแต่ Ubuntu/Debian Mirror ที่เป็น HTTP (เช่น mirror1.ku.ac.th) เท่านั้นที่ Cache ได้ผลจริง
    </li>
    <li>
        <strong>⚠️ จุดเดียวที่ถ้าล่มคือทุกทีมกระทบหมด:</strong> เพราะทุกทีมตั้ง Proxy ชี้มาที่ Server เดียวกัน หาก Apt-Cacher-Ng ล่ม จะกระทบการติดตั้ง/อัปเดตแพ็กเกจของทุกทีมพร้อมกัน ควรตรวจสอบ
        <code>systemctl status apt-cacher-ng</code> เป็นระยะระหว่างการแข่งขัน
    </li>
    <li>
        <strong>🖥 ต้องตั้งค่าให้ครบทุกเครื่องของทุกทีม:</strong> เครื่องไหนไม่ได้ตั้ง Proxy ตามส่วนที่ 2 จะไม่ได้ประโยชน์จาก Cache เลย และถ้าอินเทอร์เน็ตถูกตัดแล้วจะ `apt install` อะไรใหม่ไม่ได้เลย
    </li>
</ul>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: การติดตั้งและตั้งค่าบน Server (10.20.30.101)</h2>

<h3>1.1 ติดตั้งแพ็กเกจ</h3>
<p>ติดตั้ง `apt-cacher-ng` จาก Official Repository ของ Ubuntu:</p>
<pre><code>sudo apt update
sudo apt install -y apt-cacher-ng</code></pre>

<h3>1.2 ตั้งค่า Service (`acng.conf`)</h3>
<p>แก้ไขไฟล์คอนฟิกหลักเพื่อปรับแต่งการทำงาน:</p>
<pre><code>sudo nano /etc/apt-cacher-ng/acng.conf</code></pre>
<p>ตรวจสอบ/แก้ไข/เพิ่ม บรรทัดที่สำคัญเหล่านี้ (ค่าอื่นๆ ส่วนใหญ่ใช้ค่าเริ่มต้นได้):</p>
<pre><code># Port ที่ Service จะทำงาน (ค่าเริ่มต้นคือ 3142)
Port:3142

# IP Address ที่จะให้ Service รับการเชื่อมต่อ
# 0.0.0.0 หมายถึงรับการเชื่อมต่อจากทุก Network Interface ที่มี
BindAddress: 0.0.0.0

# (แนะนำ) บังคับให้ Apt-Cacher-Ng เชื่อมต่อออกอินเทอร์เน็ตด้วย IPv4 เท่านั้น
# เพื่อหลีกเลี่ยงปัญหาที่อาจเกิดขึ้นกับ IPv6 ในบางเครือข่าย
ConnectProto: v4

# รูปแบบ URL ที่จะอนุญาตให้ Client ร้องขอผ่าน Proxy ได้
# .* หมายถึงอนุญาตทุกรูปแบบ (จำเป็นสำหรับ HTTPS และ Repository อื่นๆ)
PassThroughPattern: .*

# ชื่อไฟล์สำหรับหน้าเว็บรายงานและจัดการ Cache
ReportPage: acng-report.html

# เปิด Log แบบละเอียด (มีประโยชน์ตอนทดสอบ)
VerboseLog: 1

# ระยะเวลาขั้นต่ำ (วัน) ที่จะเก็บไฟล์ที่ไม่มีการอ้างอิงถึงแล้ว ก่อนที่จะถูกลบ
# (ExTreshold เป็นชื่อ config ดั้งเดิม)
ExTreshold: 30

# --- (ทางเลือก) การตั้งค่าเพิ่มเติม ---
# เก็บ Deb เวอร์ชันล่าสุดเพิ่ม แม้จะไม่มีการอ้างอิงแล้ว (เช่น เก็บไว้ 2 เวอร์ชันล่าสุด)
# KeepExtraVersions: 2

# จำกัด IP หรือ Subnet ของ Client ที่อนุญาตให้ใช้งาน Cache ได้
# (ตัวอย่าง: อนุญาตเฉพาะ Localhost และ Subnet 10.20.30.0/24)
# AllowedHosts: 127.0.0.1 10.20.30.0/24
</code></pre>
<p>กด `Ctrl+X` > `Y` > `Enter` เพื่อบันทึกไฟล์</p>

<h3>1.3 เปิด Service และ Firewall</h3>
<p>สั่งให้ Service เริ่มทำงานและทำงานอัตโนมัติเมื่อ Boot เครื่อง:</p>
<pre><code># เริ่ม Service และตั้งค่าให้เริ่มอัตโนมัติ
sudo systemctl enable --now apt-cacher-ng

# โหลดการตั้งค่าใหม่ (หากมีการแก้ไข acng.conf)
sudo systemctl reload apt-cacher-ng</code></pre>
<p>หากคุณใช้งาน Firewall (เช่น UFW) ต้องเปิด Port 3142:</p>
<pre><code>sudo ufw allow 3142/tcp</code></pre>

<h3>1.4 ทดสอบ Service จากฝั่ง Server</h3>
<p>ลองใช้ `curl` เพื่อทดสอบว่า Service ตอบสนองที่ Port 3142 หรือไม่:</p>
<pre><code>curl -I http://127.0.0.1:3142/acng-report.html</code></pre>
<p>คุณควรจะเห็นผลลัพธ์ขึ้นต้นด้วย `HTTP/1.1 200 OK`</p>
<p>
  นอกจากนี้ คุณสามารถเปิดเว็บเบราว์เซอร์บนเครื่องอื่นในเครือข่าย แล้วเข้าไปที่
  <code>http://10.20.30.101:3142/acng-report.html</code>
  เพื่อดูหน้าสรุปสถานะและสถิติของ Cache ได้
</p>
<div class="image-container">
  <img src="./images/aptcacheng-01.png" alt="Clone URL on GitLab" class="content-image" />
  <p>หน้าเว็บ Report ของ Apt-Cacher-Ng แสดงสถิติการใช้งาน Cache</p>
</div>

<h3>1.5 (สำคัญมาก) อุ่นแคชล่วงหน้า (Pre-warming)</h3>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
    <strong>ต้องทำขั้นตอนนี้ตอนที่ยังมีอินเทอร์เน็ตเท่านั้น:</strong> เพราะเมื่อหมดช่วงเวลาที่กรรมการอนุญาตให้ต่ออินเทอร์เน็ตแล้ว แพ็กเกจใดก็ตามที่ยังไม่เคยถูกร้องขอผ่าน Apt-Cacher-Ng
    จะไม่สามารถดาวน์โหลดได้อีกเลย ควรรันคำสั่งด้านล่างนี้ให้ครบ <strong>ก่อน</strong> เริ่มนับเวลาที่อนุญาตให้ใช้อินเทอร์เน็ตหมด
</p>
<pre><code># สั่ง apt update โดยระบุ Proxy เป็น localhost:3142 ชั่วคราว
sudo apt -o Acquire::http::Proxy="http://127.0.0.1:3142" update

# ดาวน์โหลดแพ็กเกจที่ทุกทีมมักต้องใช้มาเก็บไว้ล่วงหน้า (เฉพาะดาวน์โหลด ไม่ติดตั้ง)
# ปรับรายชื่อแพ็กเกจให้ตรงกับที่คาดว่าทีมจะต้องใช้จริงในการแข่งขันปีนั้นๆ
sudo apt -o Acquire::http::Proxy="http://127.0.0.1:3142" --download-only install \
  build-essential curl wget git ca-certificates gnupg lsb-release \
  vim nano net-tools htop unzip software-properties-common \
  apt-transport-https ufw openssh-server
</code></pre>
<p>
  <strong>หมายเหตุ:</strong> คำสั่งนี้ดาวน์โหลดเฉพาะแพ็กเกจที่ติดตั้งผ่าน <code>apt</code> โดยตรงเท่านั้น ส่วนเครื่องมืออย่าง Docker Engine, Node.js หรือ GitLab Runner มักติดตั้งผ่าน Repository ภายนอกที่เป็น HTTPS
  (ดูส่วนที่ 4) ซึ่ง Apt-Cacher-Ng แคชให้ไม่ได้จริงตามที่อธิบายไว้ด้านบน จึงควรดาวน์โหลด Installer หรือทำการติดตั้งเครื่องมือเหล่านี้ให้เสร็จบน Server ตัวอย่าง/VM ต้นแบบตั้งแต่ตอนที่ยังมีอินเทอร์เน็ตเช่นกัน
  ไม่ควรพึ่งพา Apt-Cacher-Ng เพียงอย่างเดียว
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: การตั้งค่าเครื่อง Client (เครื่องที่จะใช้ Cache)</h2>
<p>ทำขั้นตอนนี้บนเครื่อง Ubuntu Server/Desktop **ทุกเครื่อง** ที่คุณต้องการให้ดึงแพ็กเกจผ่าน Apt-Cacher-Ng Server (<code>10.20.30.101</code>)</p>

<h3>2.1 ชี้ APT ไปยัง Proxy Server</h3>
<p>สร้างไฟล์คอนฟิกใหม่เพื่อบอกให้ APT รู้จัก Proxy ของเรา:</p>
<pre><code># สร้าง/เขียนทับไฟล์ /etc/apt/apt.conf.d/01proxy
# เพื่อกำหนด Proxy สำหรับทั้ง HTTP และ HTTPS
sudo tee /etc/apt/apt.conf.d/01proxy >/dev/null <<'EOF'
Acquire::http::Proxy  "http://10.20.30.101:3142";
Acquire::https::Proxy "http://10.20.30.101:3142";
EOF</code></pre>
<p><strong>หมายเหตุ:</strong> การตั้งค่า `https::Proxy` ก็ชี้ไปที่ `http://...` นั้นถูกต้องแล้ว เพราะ Apt-Cacher-Ng จะใช้ `PassThroughPattern` เพื่อจัดการกับการเชื่อมต่อ HTTPS เอง</p>

<h3>2.2 (แนะนำ) บังคับ APT ใช้ IPv4</h3>
<p>เพื่อป้องกันปัญหาการเชื่อมต่อในบางเครือข่าย แนะนำให้บังคับ APT ใช้ IPv4:</p>
<pre><code># สร้างไฟล์ /etc/apt/apt.conf.d/99force-ipv4
echo 'Acquire::ForceIPv4 "true";' | sudo tee /etc/apt/apt.conf.d/99force-ipv4</code></pre>

<h3>2.3 (แนะนำ) เปลี่ยนไปใช้ Mirror ในประเทศไทย</h3>
<p>การใช้ Mirror ที่อยู่ใกล้จะช่วยให้ดาวน์โหลดเร็วขึ้นและเสถียรขึ้น (ตัวอย่างนี้เปลี่ยนไปใช้ Mirror ของ ม.เกษตรฯ)</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
    <strong>สำคัญ:</strong> Ubuntu 24.04 ขึ้นไปเปลี่ยนรูปแบบไฟล์ APT Source เป็น DEB822 โดยย้าย URL จริงไปไว้ที่ <code>/etc/apt/sources.list.d/ubuntu.sources</code> แทน (ไฟล์
    <code>/etc/apt/sources.list</code> แบบเดิมจะว่างเปล่า) ให้เช็กก่อนว่าเครื่องของคุณใช้รูปแบบไหน:
    <pre><code>cat /etc/apt/sources.list.d/ubuntu.sources 2>/dev/null | head -3</code></pre>
    ถ้าเห็นเนื้อหา (ขึ้นต้นด้วย <code>Types: deb</code>) แปลว่าเป็น Ubuntu 24.04 ขึ้นไป ให้ใช้แท็บ "Ubuntu 24.04+" ด้านล่าง แต่ถ้าไฟล์ไม่มีหรือว่างเปล่า ให้ใช้แท็บ "Ubuntu 22.04 หรือเก่ากว่า" แทน
</p>

<style>
.tab { overflow: hidden; border-bottom: 1px solid #ccc; background-color: #f1f1f1; border-radius: 5px 5px 0 0;}
.tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; font-size: 17px; font-family: 'Sarabun', sans-serif;}
.tab button:hover { background-color: #ddd; }
.tab button.active { background-color: #ccc; font-weight: bold;}
.tabcontent { display: none; padding: 20px 12px; border: 1px solid #ccc; border-top: none; border-radius: 0 0 5px 5px; background-color: white;}
</style>
<div class="tab">
  <button class="tablinks active" data-target-tab="AcngMirror2404">Ubuntu 24.04 ขึ้นไป (DEB822)</button>
  <button class="tablinks" data-target-tab="AcngMirrorOld">Ubuntu 22.04 หรือเก่ากว่า</button>
</div>
<div id="AcngMirror2404" class="tabcontent" style="display: block">
  <pre><code># แก้ไขไฟล์ ubuntu.sources (รูปแบบ DEB822) โดยแทนที่ URI ของ archive และ security ด้วย URL ของ KU Mirror
sudo sed -i \
  -e 's|^URIs:.*archive\.ubuntu\.com/ubuntu/\?|URIs: http://mirror1.ku.ac.th/ubuntu|' \
  -e 's|^URIs:.*security\.ubuntu\.com/ubuntu/\?|URIs: http://mirror1.ku.ac.th/ubuntu|' \
  /etc/apt/sources.list.d/ubuntu.sources</code></pre>
</div>
<div id="AcngMirrorOld" class="tabcontent">
  <pre><code># แก้ไขไฟล์ sources.list โดยแทนที่ URL หลักและ Security ด้วย URL ของ KU Mirror
sudo sed -i 's|http://\([a-z.]*\)\?archive.ubuntu.com/ubuntu|http://mirror1.ku.ac.th/ubuntu|g; s|http://security.ubuntu.com/ubuntu|http://mirror1.ku.ac.th/ubuntu|g' /etc/apt/sources.list</code></pre>
</div>

<h3>2.4 ทดสอบใช้งาน Cache จาก Client</h3>
<p>ลองรันคำสั่ง APT ต่างๆ เพื่อดูว่าทำงานผ่าน Cache หรือไม่:</p>
<pre><code># ล้าง Cache ของ APT บนเครื่อง Client ก่อน เพื่อให้เห็นผลชัดเจน
sudo apt clean && sudo rm -rf /var/lib/apt/lists/*

# ครั้งที่ 1: apt update (ควรจะดาวน์โหลดไฟล์ Index ผ่าน Proxy)
sudo apt update

# ครั้งที่ 1: apt upgrade (ควรจะดาวน์โหลดแพ็กเกจใหม่ผ่าน Proxy)
sudo apt -y upgrade

# ครั้งที่ 1: apt install (ควรจะดาวน์โหลดแพ็กเกจใหม่ผ่าน Proxy)
sudo apt -y install curl vim

# --- ลองรันครั้งที่ 2 ---
# ครั้งที่ 2: apt update (ควรจะเร็วขึ้นมาก เพราะใช้ Cache)
sudo apt update</code></pre>
<p>
  หลังจากรันครั้งแรกแล้ว คุณสามารถกลับไปดูหน้า Report ของ Apt-Cacher-Ng (<code>http://10.20.30.101:3142/acng-report.html</code>) จะเห็นว่าค่า "Hits" (การดึงจาก Cache) เพิ่มขึ้น และ "Misses"
  (การดาวน์โหลดใหม่) ลดลงในการรันครั้งถัดๆ ไป
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: การดูแลรักษาและแก้ไขปัญหา</h2>

<h3>3.1 ดูสถิติและจัดการ Cache</h3>
<ul>
  <li>
    <strong>หน้าเว็บ Report:</strong>
    <code>http://10.20.30.101:3142/acng-report.html</code><br />
    หน้านี้จะแสดงสถิติการใช้งาน Cache (Hits/Misses), จำนวนไฟล์ที่เก็บไว้, และมีปุ่มสำหรับสั่ง "Expire" (ลบไฟล์เก่าตามเกณฑ์ `ExTreshold`) หรือ "Clean Up" (ล้าง Cache บางส่วน) ได้
  </li>
  <li>
    <strong>คำสั่ง Expire:</strong> สั่งลบไฟล์เก่าด้วยตนเองผ่าน Command Line
    <pre><code>sudo /usr/lib/apt-cacher-ng/acngtool -e</code></pre>
  </li>
  <li>
    <strong>ดู Log:</strong> ติดตามการทำงานแบบ Real-time
    <pre><code>sudo tail -f /var/log/apt-cacher-ng/apt-cacher.log</code></pre>
  </li>
</ul>

<h3>3.2 เคล็ดลับการแก้ไขปัญหา</h3>
<ul>
  <li>
    <strong>จาก Client:</strong>
    <ul>
      <li>
        ทดสอบว่า Client มองเห็น Server และ Port เปิดอยู่หรือไม่:
        <pre><code>curl -I http://10.20.30.101:3142/acng-report.html</code></pre>
        (ควรได้ 200 OK)
      </li>
      <li>
        ลองข้าม Proxy ชั่วคราวเพื่อทดสอบว่าปัญหาอยู่ที่ Proxy หรือไม่:
        <pre><code>sudo apt -o Acquire::http::Proxy= -o Acquire::https::Proxy= update</code></pre>
      </li>
    </ul>
  </li>
  <li>
    <strong>จาก Server:</strong>
    <ul>
      <li>
        ตรวจสอบสถานะ Service:
        <pre><code>systemctl status apt-cacher-ng</code></pre>
      </li>
      <li>
        ทดสอบว่า Server สามารถเชื่อมต่อออกอินเทอร์เน็ต (ด้วย IPv4) ไปยัง Mirror ได้หรือไม่:
        <pre><code>curl -4 -I http://mirror1.ku.ac.th/ubuntu/dists/$(. /etc/os-release;echo $VERSION_CODENAME)/InRelease</code></pre>
        (ควรได้ 200 OK) หากไม่ได้ ให้ตรวจสอบการตั้งค่า Network/Firewall/NAT ที่ Router หลักของเครือข่าย
      </li>
    </ul>
  </li>
</ul>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: ตัวอย่างขั้นสูง - การเพิ่ม Docker Repository ผ่าน Cache</h2>
<p>แม้แต่การเพิ่ม Repository ภายนอก เช่น ของ Docker ก็สามารถทำผ่าน Apt-Cacher-Ng ได้เช่นกัน แต่ต้องใช้คำสั่ง `curl` ที่ระบุ Proxy และบังคับ IPv4</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
    <strong>ข้อจำกัด:</strong> Docker Repository เป็น HTTPS ล้วน การตั้งค่าในส่วนนี้ช่วยให้ Client เชื่อมต่อออกไปได้ผ่าน Proxy ตัวเดียวกัน แต่ <strong>ไม่ได้ถูกแคชจริง</strong>
    (ดูหัวข้อ "ข้อควรระวัง" ด้านบนของหน้านี้) ดังนั้นขั้นตอนนี้ต้องทำสำเร็จ <strong>ตอนที่ยังมีอินเทอร์เน็ตอยู่เท่านั้น</strong> และต้องทำซ้ำทุกเครื่องที่ต้องใช้ Docker หากอินเทอร์เน็ตถูกตัดไปแล้ว
    ขั้นตอนนี้จะใช้ไม่ได้อีก
</p>
<pre><code># สร้างโฟลเดอร์สำหรับเก็บ Key (ถ้ายังไม่มี)
sudo mkdir -p /etc/apt/keyrings

# ดาวน์โหลด GPG Key ของ Docker ผ่าน Proxy (-x) ด้วย IPv4 (-4)
# พร้อมตั้งค่า Timeout เผื่อเครือข่ายช้า
curl -4 --connect-timeout 10 --max-time 60 \
  -x http://10.20.30.101:3142 \
  -fsSL https://download.docker.com/linux/ubuntu/gpg \
  -o /tmp/docker.gpg

# แปลง Key และติดตั้ง
sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg /tmp/docker.gpg
sudo chmod 0644 /etc/apt/keyrings/docker.gpg

# เพิ่ม Docker Repository Source List
echo "deb [arch=$(dpkg --print-architecture) \
signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release; echo $VERSION_CODENAME) stable" \
| sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# อัปเดตรายการแพ็กเกจ (ซึ่งจะวิ่งผ่าน Proxy โดยอัตโนมัติ)
sudo apt update
</code></pre>
<p>หลังจากนี้ การติดตั้ง Docker (`sudo apt install docker-ce ...`) ก็จะใช้ Cache จาก Apt-Cacher-Ng ได้ตามปกติ</p>
