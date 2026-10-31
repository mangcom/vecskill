<h1>การติดตั้ง Node.js 22 (LTS) บน Ubuntu 24.04</h1>
<p>
    Node.js คือ Runtime Environment สำหรับรัน JavaScript ฝั่ง Server ในคู่มือนี้เราจะติดตั้ง <strong>Node.js เวอร์ชัน 22 (LTS - Long Term Support)</strong> ซึ่งเป็นเวอร์ชันที่เสถียรและแนะนำสำหรับการใช้งานจริง
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px;">
    <strong>ทำไมต้อง NodeSource?</strong><br>
    Repository ปกติของ Ubuntu มักจะมี Node.js เวอร์ชันเก่า (เช่น v18 หรือ v20) การใช้ <strong>NodeSource</strong> จะช่วยให้เราเลือกติดตั้งเวอร์ชันที่ต้องการ (เช่น v22, v23) ได้อย่างแม่นยำและได้รับอัปเดต Patch ความปลอดภัยที่รวดเร็วกว่า
</p>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 1: อัปเดตระบบ</h2>
<p>
    ก่อนเริ่มติดตั้งซอฟต์แวร์ใดๆ ควรตรวจสอบและอัปเดตแพ็กเกจในเครื่องให้เป็นปัจจุบันเพื่อป้องกันปัญหาความเข้ากันได้
</p>
<pre><code>sudo apt update && sudo apt upgrade -y</code></pre>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 2: ติดตั้ง Dependency พื้นฐาน</h2>
<p>
    เราจำเป็นต้องติดตั้งเครื่องมือเหล่านี้เพื่อใช้ในการดาวน์โหลดไฟล์ (<code>curl</code>) และจัดการกุญแจความปลอดภัย (<code>gnupg</code>)
</p>
<pre><code>sudo apt install -y ca-certificates curl gnupg</code></pre>
<ul>
    <li><strong>ca-certificates:</strong> ใบรับรองความปลอดภัยเพื่อให้เครื่องเรารู้จักเว็บ HTTPS ทั่วโลก</li>
    <li><strong>curl:</strong> เครื่องมือสำหรับดาวน์โหลดไฟล์ผ่าน Command Line</li>
    <li><strong>gnupg:</strong> เครื่องมือสำหรับจัดการ GPG Key (ใช้ยืนยันว่าไฟล์ที่โหลดมาเป็นของจริง ไม่ถูกปลอมแปลง)</li>
</ul>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 3: เพิ่ม NodeSource Repository (Node 22)</h2>
<p>
    นี่คือขั้นตอนสำคัญที่สุด เป็นการบอกให้ Ubuntu รู้จักแหล่งดาวน์โหลดของ Node.js เวอร์ชัน 22
</p>

<h3>3.1 เพิ่ม GPG Key (กุญแจยืนยันตัวตน)</h3>
<p>
    สร้างโฟลเดอร์สำหรับเก็บกุญแจ และดาวน์โหลดกุญแจจาก NodeSource มาเก็บไว้
</p>
<pre><code>sudo mkdir -p /etc/apt/keyrings

curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg</code></pre>

<h3>3.2 เพิ่ม Repository ลงใน Sources List</h3>
<p>
    สร้างไฟล์ <code>nodesource.list</code> เพื่อชี้เป้าไปยัง Server ของ NodeSource
</p>
<pre><code>echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list</code></pre>
<p style="font-size: 0.9em; color: #666;">
    <em>เกร็ดความรู้: คำว่า <code>nodistro</code> ในคำสั่ง หมายถึงใช้ได้กับทุก Distro (Ubuntu/Debian) โดยไม่ต้องระบุชื่อรุ่น (เช่น noble หรือ jammy) ทำให้คำสั่งนี้ใช้ได้กับ Ubuntu ทุกเวอร์ชัน</em>
</p>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 4: ติดตั้ง Node.js 22</h2>
<p>
    เมื่อเพิ่ม Repo เสร็จแล้ว ให้สั่ง update เพื่อดึงรายการแพ็กเกจใหม่ แล้วสั่งติดตั้งได้เลย<br>
    # อัปเดตเพื่อให้ apt เห็น package จาก NodeSource ที่เพิ่งเพิ่มไป
</p>
<pre><code>sudo apt update
</code></pre>

<p># ติดตั้ง nodejs (คำสั่งนี้จะได้ทั้ง node และ npm)</p>
<pre><code>sudo apt install -y nodejs</code></pre>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 5: ตรวจสอบและติดตั้งเครื่องมือเสริม</h2>

<h3>5.1 ตรวจสอบเวอร์ชัน</h3>
<p>เช็คว่าติดตั้งสำเร็จหรือไม่</p>
<pre><code>node -v
# ควรขึ้น v22.x.x

npm -v
# ควรขึ้น 10.x.x</code></pre>

<h3>5.2 (แนะนำเพิ่ม) ติดตั้ง Build Tools</h3>
<p>
    บางครั้งเมื่อเราสั่ง <code>npm install</code> แพ็กเกจบางตัวอาจต้องการการ "Compile" (เช่น bcrypt, sqlite3) ซึ่งต้องใช้ C++ Compiler
    แนะนำให้ติดตั้งชุด <code>build-essential</code> เผื่อไว้เสมอครับ
</p>
<pre><code>sudo apt install -y build-essential</code></pre>

<hr style="margin: 40px 0;">

<h2>ทดสอบการใช้งาน (Hello World)</h2>
<p>ลองสร้าง Web Server ง่ายๆ เพื่อทดสอบว่า Node.js ทำงานได้จริง</p>
<pre><code># สร้างไฟล์ test.js
echo "console.log('Node.js is working correctly!');" > test.js

# รันไฟล์
node test.js</code></pre>
<p>หากหน้าจอแสดงคำว่า <code>Node.js is working correctly!</code> แสดงว่าพร้อมใช้งานครับ</p>