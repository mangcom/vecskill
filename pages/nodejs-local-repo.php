<h1>การสร้าง Node.js Local Repository (Simple Repo)</h1>
<p>
  คู่มือนี้จะสอนวิธีสร้าง Repository สำหรับแจกจ่าย <strong>Node.js</strong> ภายในเครือข่ายของคุณเอง โดยใช้วิธีการสร้าง Index แบบง่าย (Simple Repo) ซึ่งเหมาะสำหรับการล็อกเวอร์ชัน Node.js
  ให้ตรงกันทุกเครื่อง และช่วยให้ติดตั้งได้โดยไม่ต้องออกอินเทอร์เน็ต
</p>
<p style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px">
  <strong>แนวคิด:</strong> เราจะดาวน์โหลดไฟล์ติดตั้ง (.deb) ของ Node.js มาเก็บไว้ที่ Server แล้วใช้คำสั่ง <code>dpkg-scanpackages</code> สร้างสารบัญ (Index) ขึ้นมาเอง
  เพื่อให้เครื่องลูกข่ายมองเห็นโฟลเดอร์นี้เป็น Repository
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
    <strong>ทำไมต้องมีหน้านี้แยกจาก Apt-Cacher-Ng?</strong> เพราะ NodeSource Repository เป็น HTTPS ล้วน ซึ่ง Apt-Cacher-Ng ได้แค่ "ส่งผ่าน" (Tunnel) ไม่ได้แคชไฟล์จริง
    (ดูหัวข้อ <a href="#" onclick="document.querySelector('a[data-page=\'apt-cacher-ng\']').click();">Apt-Cacher-Ng</a>) ถ้าอินเทอร์เน็ตถูกตัดแล้ว เครื่องที่ยังไม่เคยติดตั้ง Node.js จะติดตั้งผ่าน Apt-Cacher-Ng
    ไม่ได้อีก วิธีในหน้านี้คือการสร้าง Repository ของเราเองแบบ HTTP ธรรมดา (ไม่ต้องพึ่ง HTTPS ของ NodeSource อีกต่อไป) จึงใช้งานได้แน่นอนแม้ไม่มีอินเทอร์เน็ตแล้ว
</p>

<hr style="margin: 40px 0" />

<h2>Phase 1: เตรียมเครื่อง Server (Host)</h2>
<p>เราต้องเตรียมเครื่องมือสร้าง Index (dpkg-dev) และติดตั้ง Source ของ Node.js ลงในเครื่อง Server ก่อน เพื่อให้ Server รู้จักแพ็กเกจตัวล่าสุด</p>

<h3>1.1 ติดตั้งเครื่องมือจำเป็น</h3>
<pre><code>sudo apt update
sudo apt install dpkg-dev nginx -y</code></pre>

<h3>1.2 เพิ่ม NodeSource Repository ลงในเครื่อง Server</h3>
<p>
  เพิ่ม Repository ของ NodeSource ด้วยวิธี Manual (ปลอดภัยกว่าการสั่ง <code>curl | sudo bash</code> ตรงๆ เพราะเราเห็นเนื้อหาที่ดาวน์โหลดมาก่อนนำไปใช้งานจริง)
  เลือกเวอร์ชันที่ต้องการผ่านตัวแปร <code>NODE_MAJOR</code> (ตัวอย่างนี้ใช้ 24 ซึ่งเป็น LTS ปัจจุบัน) วิธีนี้เหมือนกับที่ใช้ในหัวข้อ
  <a href="#" onclick="document.querySelector('a[data-page=\'nodejs-install\']').click();">การติดตั้ง Node.js (Online)</a> ทุกประการ
</p>
<pre><code>sudo apt install -y ca-certificates curl gnupg
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg

NODE_MAJOR=24
sudo tee /etc/apt/sources.list.d/nodesource.sources > /dev/null <<EOF
Types: deb
URIs: https://deb.nodesource.com/node_${NODE_MAJOR}.x/
Suites: nodistro
Components: main
Signed-By: /etc/apt/keyrings/nodesource.gpg
EOF

sudo apt update</code></pre>

<h3>1.3 สร้างโฟลเดอร์สำหรับเก็บไฟล์</h3>
<p>เราจะใช้โฟลเดอร์นี้เป็นที่อยู่ของ Repository ของเรา</p>
<pre><code>sudo mkdir -p /var/www/html/nodejs-local</code></pre>

<hr style="margin: 40px 0" />

<h2>Phase 2: สร้าง Script สำหรับดาวน์โหลด (Sync Script)</h2>
<p>เราจะเขียน Script ที่ทำหน้าที่ 3 อย่างคือ: ลบไฟล์เก่า, ดาวน์โหลดไฟล์ใหม่, และสร้างไฟล์สารบัญ (Packages.gz)</p>

<h3>2.1 สร้างไฟล์ Script</h3>
<pre><code>sudo nano /usr/local/bin/update-nodejs.sh</code></pre>

<h3>2.2 ใส่โค้ด Script</h3>
<p>คัดลอกโค้ดด้านล่างนี้ไปวางในไฟล์ (สคริปต์นี้ปรับแก้เรื่อง Permission ให้แล้ว)</p>
<pre><code class="language-bash">#!/bin/bash

# --- Config ---
REPO_DIR="/var/www/html/nodejs-local"

echo "========================================"
echo "Starting Node.js Local Repo Update..."
echo "========================================"

# 1. ตรวจสอบและสร้างโฟลเดอร์
if [ ! -d "$REPO_DIR" ]; then
    mkdir -p $REPO_DIR
fi

# 2. เข้าไปที่โฟลเดอร์
cd $REPO_DIR

# 3. ลบไฟล์เก่าทิ้ง (เพื่อป้องกันไฟล์ขยะสะสม)
rm -f *.deb

# 4. ดาวน์โหลดไฟล์ (ใช้สิทธิ์ _apt ชั่วคราวเพื่อแก้ Warning unsandboxed)
echo "Downloading latest Node.js package..."
chown _apt:root $REPO_DIR
apt-get download nodejs

# 5. สร้าง Index (สำคัญมาก: บรรทัดนี้ทำให้ Client มองเห็น)
echo "Scanning packages to create index..."
# ใช้ sudo/root เพื่อ scan
dpkg-scanpackages . /dev/null | gzip -9c > Packages.gz

# 6. คืนสิทธิ์ให้ Web Server (Nginx/Apache) อ่านได้
echo "Fixing Permissions..."
chown -R www-data:www-data $REPO_DIR
chmod -R 755 $REPO_DIR

echo "========================================"
echo "Node.js Update Complete!"
echo "Files in repo:"
ls -lh $REPO_DIR
echo "========================================"
</code></pre>

<h3>2.3 ให้สิทธิ์และทดสอบรัน</h3>
<pre><code># ให้สิทธิ์ execute
sudo chmod +x /usr/local/bin/update-nodejs.sh

# สั่งรัน Script
sudo /usr/local/bin/update-nodejs.sh</code></pre>
<p><strong>ตรวจสอบผลลัพธ์:</strong> ในบรรทัดสุดท้าย คุณจะต้องเห็นไฟล์ <code>nodejs_xx.xx.deb</code> และ <code>Packages.gz</code> ปรากฏขึ้นมา</p>

<hr style="margin: 40px 0" />

<h2>Phase 3: ตั้งค่าเครื่องลูกข่าย (Client)</h2>
<p>ไปที่เครื่องนักเรียน (Ubuntu Client) เพื่อชี้มาที่ Repo ที่เราเพิ่งสร้าง</p>

<h3>3.1 สร้างไฟล์ sources.list ใหม่</h3>
<pre><code>sudo nano /etc/apt/sources.list.d/local-nodejs.list</code></pre>

<h3>3.2 เพิ่ม URL ของ Repository</h3>
<p>เปลี่ยน <code>10.20.30.101</code> เป็น IP Server ของคุณ</p>
<div class="code-container">
  <pre><code># Node.js Local Repo
# [trusted=yes] คือคำสั่งพิเศษ บอกว่า "เชื่อใจ Repo นี้เถอะ ไม่ต้องตรวจ GPG Key"
# เพราะ Repo ที่เราทำมือเอง (Simple Repo) จะไม่มีการเซ็น Digital Signature

deb [trusted=yes] http://10.20.30.101/nodejs-local ./</code></pre>
</div>
<p style="color: #666; font-size: 0.9em">
  <em>สังเกต: ด้านหลังสุดเป็น <code>./</code> (เว้นวรรค จุด สแลช) ไม่ต้องใส่ main หรือ noble เหมือน repo ปกติ</em>
</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
    <strong>ข้อควรระวัง:</strong> <code>[trusted=yes]</code> ปิดการตรวจสอบลายเซ็นของแพ็กเกจทั้งหมดจาก Repo นี้ APT จึงไม่สามารถยืนยันได้ว่าไฟล์ไม่ถูกแก้ไขระหว่างทาง ยอมรับได้เฉพาะในเครือข่ายภายในที่ควบคุมได้
    (เช่นในการแข่งขัน) เท่านั้น ไม่ควรใช้วิธีนี้กับ Repository ที่เข้าถึงผ่านเครือข่ายสาธารณะ
</p>

<hr style="margin: 40px 0" />

<h2>Phase 4: ทดสอบติดตั้ง (Verify)</h2>
<p>ที่เครื่องลูกข่าย ให้ลองรันคำสั่งต่อไปนี้เพื่อทดสอบ</p>

<h3>4.1 อัปเดตรายการแพ็กเกจ</h3>
<pre><code>sudo apt update</code></pre>
<p>ผลลัพธ์ต้องไม่ขึ้นตัวแดง Error (อาจจะขึ้น <code>Get:1 ... InRelease [Ignored]</code> ได้ ถือว่าปกติสำหรับ Trusted Repo)</p>

<h3>4.2 ตรวจสอบที่มาของเวอร์ชัน</h3>
<pre><code>apt policy nodejs</code></pre>
<p>
  <strong>ผลลัพธ์ที่ถูกต้อง:</strong><br />
  บรรทัด <code>Candidate:</code> ควรเป็นเวอร์ชันใหม่ (เช่น 22.x)<br />
  และบรรทัดล่างสุดควรชี้ไปที่ <code>http://10.20.30.101/nodejs-local</code>
</p>

<h3>4.3 ติดตั้งจริง</h3>
<pre><code>sudo apt install nodejs -y</code></pre>
<p>หากติดตั้งผ่านและได้ Node.js เวอร์ชันที่ต้องการ ก็ถือว่าจบกระบวนการสร้าง Local Repo ครับ! 🎉</p>
