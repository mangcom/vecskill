<h1>การใช้งาน Git เบื้องต้นกับ GitLab Server</h1>
<p>
  หลังจากที่เราได้ติดตั้งและตั้งค่าระบบทั้งหมดเรียบร้อยแล้ว ในบทนี้เราจะมาดูขั้นตอนการใช้งานจริงในฝั่งของนักพัฒนา (Developer) ซึ่งก็คือการนำซอร์สโค้ดของโปรเจกต์เราเข้าไปจัดเก็บและควบคุมเวอร์ชันบน
  GitLab Server โดยใช้คำสั่ง Git พื้นฐาน
</p>
<p>
  Git คือระบบควบคุมเวอร์ชัน (Version Control System) ที่ทำหน้าที่ติดตามการเปลี่ยนแปลงของไฟล์ต่างๆ ในโปรเจกต์ของเรา ทำให้เราสามารถย้อนกลับไปดูเวอร์ชันก่อนหน้า, ทำงานร่วมกับผู้อื่นได้ง่าย, และสร้างสาขา
  (Branch) เพื่อพัฒนาฟีเจอร์ใหม่ๆ ได้อย่างปลอดภัย
</p>

<h3>สิ่งที่ต้องเตรียมก่อนเริ่มต้น</h3>
<ul>
  <li><strong>ติดตั้ง Git บนเครื่องคอมพิวเตอร์ของคุณ:</strong> หากยังไม่มี สามารถดาวน์โหลดและติดตั้งได้จาก <a href="https://git-scm.com/downloads" target="_blank">git-scm.com</a></li>
  <li>
    <strong>สร้างโปรเจกต์ว่าง (Blank Project) บน GitLab:</strong> ล็อกอินเข้า <code>http://gitlab.vecskill.ovec</code> และสร้างโปรเจกต์ใหม่ไว้รอ ตัวอย่างเช่น โปรเจกต์ชื่อ `demo-task` ภายใต้ Group
    `devops_app`
  </li>
  <li><strong>เตรียมโฟลเดอร์โปรเจกต์บนเครื่องของคุณ:</strong> สร้างโฟลเดอร์ว่างๆ สำหรับโปรเจกต์นี้บนเครื่องคอมพิวเตอร์ของคุณ</li>
</ul>

<hr style="margin: 40px 0" />

<h2>ขั้นตอนการนำโปรเจกต์ขึ้น GitLab</h2>
<p>เราจะทำตามขั้นตอนมาตรฐานในการนำโค้ดจากเครื่อง local ของเรา push ขึ้นไปเก็บไว้ที่ GitLab เป็นครั้งแรก</p>

<h3>ขั้นตอนที่ 1: สร้างโฟลเดอร์และไฟล์เริ่มต้น</h3>
<p>
  เปิด Terminal หรือ Command Prompt ขึ้นมา แล้วสร้างโฟลเดอร์สำหรับโปรเจกต์ใหม่ของคุณ<br />
  # สร้างโฟลเดอร์และเข้าไปข้างใน<br />
  # สร้างไฟล์ README.md สำหรับทดสอบ
</p>
<pre><code>mkdir demo-task
cd demo-task
echo "# Demo Task Project" >> README.md
</code></pre>

<h3>ขั้นตอนที่ 2: เริ่มต้นใช้งาน Git (`git init`)</h3>
<p>คำสั่งนี้จะเปลี่ยนโฟลเดอร์ธรรมดาของคุณให้กลายเป็น "Git Repository" โดยการสร้างโฟลเดอร์ย่อยที่ซ่อนอยู่ชื่อว่า <code>.git</code> เพื่อเริ่มติดตามการเปลี่ยนแปลงทั้งหมด</p>
<pre><code>git init</code></pre>
<p>คุณจะเห็นข้อความตอบกลับว่า "Initialized empty Git repository..."</p>

<h3>ขั้นตอนที่ 3: เชื่อมต่อกับ GitLab Server (`git remote add`)</h3>
<p>ขั้นตอนนี้คือการ "บอก" Git ในเครื่องของเราว่า Repository ปลายทางบนเซิร์ฟเวอร์ (ที่เราเรียกว่า "Remote") อยู่ที่ไหน เราจะตั้งชื่อ Remote หลักของเราว่า <code>origin</code></p>
<ol>
  <li>
    ไปที่หน้าโปรเจกต์ของคุณบน GitLab แล้วคลิกที่ปุ่มสีฟ้า "Clone" เพื่อคัดลอก URL แบบ HTTP
    <div class="image-container">
      <img src="./images/10-gitlab-01.png" alt="Clone URL on GitLab" class="content-image" />
      <p>คัดลอก Clone with HTTP URL จากหน้าโปรเจกต์บน GitLab</p>
    </div>
  </li>
  <li>
    ใช้คำสั่ง <code>git remote add</code> ใน Terminal ของคุณ
    <pre><code>git remote add origin http://gitlab.vecskill.ovec/devops_app/demo-task.git</code></pre>
  </li>
</ol>

<h3>ขั้นตอนที่ 4: เพิ่มไฟล์เข้าสู่ Staging Area (`git add`)</h3>
<p>
  ก่อนที่เราจะบันทึกการเปลี่ยนแปลง (Commit) เราต้องเลือกว่าจะเอาไฟล์ไหนบ้างเข้าสู่การบันทึกครั้งนี้ ขั้นตอนนี้เรียกว่าการ "Stage" ไฟล์ คำสั่ง
  <code>git add .</code> คือการเลือกไฟล์ที่มีการเปลี่ยนแปลงทั้งหมดในโฟลเดอร์ปัจจุบัน<br />
  # เพิ่มไฟล์ทั้งหมดที่เปลี่ยนแปลงเข้าสู่ Staging Area
</p>
<pre><code>git add .</code></pre>

<h3>ขั้นตอนที่ 5: บันทึกการเปลี่ยนแปลง (`git commit`)</h3>
<p>การ Commit คือการ "ถ่ายภาพ" หรือสร้าง Snapshot ของไฟล์ที่เราได้ Stage ไว้ พร้อมกับแนบข้อความ (Message) เพื่ออธิบายว่าการเปลี่ยนแปลงครั้งนี้เกี่ยวกับอะไร <br /># ใช้ -m เพื่อใส่ commit message</p>
<pre><code>git commit -m "Initial project with README file"</code></pre>

<h3>ขั้นตอนที่ 6: เปลี่ยนชื่อ Branch ให้เป็น main (`git branch`)</h3>
<p>ใช้ -M เพื่อบังคับเปลี่ยนชื่อ branch เป็น main</p>
<pre><code>git branch -M main</code></pre>

<h3>ขั้นตอนที่ 7: Push โค้ดขึ้นไปยัง GitLab (`git push`)</h3>
<p>
  นี่คือขั้นตอนสุดท้าย คือการส่งข้อมูล Commit ทั้งหมดจากเครื่องของเราขึ้นไปยัง Remote ที่ชื่อ <code>origin</code> บน GitLab Server<br />
  # -u origin main คือการตั้งค่าให้ branch 'main' ของเราติดตาม 'origin/main'<br />
  # ทำให้ครั้งต่อไปเราสามารถใช้แค่ git push ได้เลย
</p>
<pre><code>git push -u origin main</code></pre>
<p>Git อาจจะถาม Username และ Password สำหรับล็อกอิน GitLab ของคุณ ให้กรอกข้อมูลให้ถูกต้อง</p>

<h3>ตรวจสอบผลลัพธ์และดู Pipeline ทำงาน!</h3>
<p>หลังจาก Push สำเร็จ ให้คุณกลับไปที่หน้าโปรเจกต์บน GitLab แล้วรีเฟรช คุณจะเห็นไฟล์ <code>README.md</code> ที่คุณสร้างปรากฏขึ้นมา</p>
<p style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px">
  <strong>และที่สำคัญที่สุด:</strong> ลองคลิกไปที่เมนู <strong>CI/CD > Pipelines</strong> คุณจะเห็นว่า Pipeline ใหม่ได้เริ่มทำงานโดยอัตโนมัติแล้ว!
  นี่คือผลลัพธ์จากการตั้งค่าทั้งหมดที่เราทำมาตั้งแต่บทที่ 5 การ `git push` ครั้งนี้คือตัวจุดชนวนให้กระบวนการ CI/CD ทั้งหมดเริ่มต้นขึ้น
</p>
<div class="image-container">
  <img src="https://i.imgur.com/i9dY6Wn.png" alt="GitLab Pipeline Running after push" class="content-image" />
  <p>Pipeline เริ่มทำงานอัตโนมัติทันทีหลังจาก git push สำเร็จ</p>
</div>
<p>จากนี้ไป ทุกครั้งที่คุณและทีมทำการ `git push` การเปลี่ยนแปลงใหม่ๆ ขึ้นมา, GitLab CI/CD ก็จะทำงานตามที่คุณกำหนดไว้ในไฟล์ <code>.gitlab-ci.yml</code> โดยอัตโนมัติ</p>

<hr style="margin: 40px 0" />

<h2>ตัวอย่างสถานการณ์จริง: นำโปรเจกต์ที่มีอยู่แล้วขึ้น GitLab (เช่น โปรเจกต์ PES)</h2>
<p>
  ตัวอย่างด้านบนเริ่มจากโฟลเดอร์ว่างเปล่า แต่สถานการณ์ที่เจอบ่อยกว่าคือการนำ <strong>โปรเจกต์ที่มีโค้ดอยู่แล้ว</strong> (เช่น ดาวน์โหลดไฟล์ตัวอย่างมาทั้งก้อน หรือพัฒนาไว้บนเครื่องตัวเองมาสักพักแล้ว)
  มาเชื่อมต่อกับ GitLab เป็นครั้งแรก ซึ่งมีรายละเอียดเพิ่มเติมที่ต้องระวัง 2 เรื่องหลักๆ คือ <strong>การยืนยันตัวตนด้วย Personal Access Token</strong> (GitLab ไม่รับรหัสผ่านบัญชีตรงๆ ผ่าน HTTPS)
  และ <strong>การป้องกันไฟล์ลับ (เช่น <code>.env</code>) หลุดขึ้น Repository</strong> ตัวอย่างนี้ใช้โปรเจกต์ PES (ตามที่ใช้ในบท
  <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a>) ภายใต้ Group <code>devops</code> เป็นตัวอย่างประกอบ
</p>

<h3>ขั้นที่ 0: ลบ <code>.git</code> เดิม (ถ้ามี)</h3>
<p>
  ถ้าโฟลเดอร์โปรเจกต์ที่ได้มาเคยถูก Clone หรือเชื่อมกับ Git มาก่อน (เช่น คัดลอกมาจากเครื่องอื่น หรือโหลดมาจาก Repository อื่น) อาจมีโฟลเดอร์ซ่อน <code>.git</code> ติดมาด้วย ซึ่งจะยังจำ Remote เดิมของเจ้าของเดิมอยู่
  ถ้าไม่ลบออกก่อน ขั้นตอน <code>git push</code> ด้านล่างอาจพยายามส่งขึ้น Repository เดิมแทนที่จะเป็นของคุณเอง (หรือ Error ว่ามี Remote ชื่อ <code>origin</code> อยู่แล้ว) ให้ตรวจสอบและลบทิ้งก่อนเสมอ:
</p>
<pre><code class="language-powershell"># Windows (Command Prompt / PowerShell)
rmdir /s /q .git</code></pre>
<pre><code class="language-bash"># macOS / Linux
rm -rf .git</code></pre>

<h3>ขั้นที่ 1: สร้าง Personal Access Token บน GitLab</h3>
<p>
  GitLab ไม่อนุญาตให้ใช้รหัสผ่านบัญชีปกติตอน Push ผ่าน HTTPS โดยตรง (เพื่อความปลอดภัย) ต้องสร้าง Personal Access Token มาใช้แทนรหัสผ่านก่อน (ถ้าเคยสร้างไว้แล้วจากบท
  <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a> ใช้ Token เดิมได้เลย ข้ามขั้นตอนนี้ไปขั้นที่ 2 ได้):
</p>
<ol>
  <li>ล็อกอินเข้า GitLab แล้วคลิกรูปโปรไฟล์มุมขวาบน &gt; <strong>Edit profile</strong></li>
  <li>ไปที่เมนู <strong>Access Tokens</strong> ทางซ้าย</li>
  <li>ตั้งชื่อ Token, เลือกวันหมดอายุ, ติ๊ก Scope <code>write_repository</code> (ครอบคลุม <code>read_repository</code> ด้วยอยู่แล้ว)</li>
  <li>กด <strong>Create personal access token</strong> แล้วคัดลอกค่าที่ขึ้นต้นด้วย <code>glpat-</code> เก็บไว้ทันที (หน้านี้แสดงให้เห็นครั้งเดียวเท่านั้น)</li>
</ol>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-01.png" alt="Edit profile" class="content-image" />
  <p>ภาพ: หน้า Edit profile บน GitLab</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-02.png" alt="Access Personal access tokens" class="content-image" />
  <p>ภาพ: หน้า Access Personal access tokens บน GitLab</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-03.png" alt="Generate Personal access token" class="content-image" />
  <p>ภาพ: หน้า Generate token (Generate Personal access token) บน GitLab เลือกว่าจะใช้แบบไหน</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-04.png" alt="Token name" class="content-image" />
  <p>ภาพ: Generate legacy token ตั้งชื่อ รายละเอียด วันหมดอายุ และเลือก read_repository และ write_repository</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-05.png" alt="Generated token Button" class="content-image" />
  <p>ภาพ: ปุ่ม Generate token หลังจากตั้งค่าแล้ว</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-06.png" alt="View token" class="content-image" />
  <p>ภาพ: หน้า View token บน GitLab</p>
</div>
<div class="image-container">
  <img src="./images/basicgit/basic-git-01-07.png" alt="Copy token" class="content-image" />
  <p>ภาพ: หน้า Copy token บน GitLab</p>
</div>

<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>เก็บ Token เป็นความลับเสมอ:</strong> ห้ามวาง Token นี้ลงในไฟล์ที่จะ Commit ขึ้น Git, ห้ามแชร์ในแชทหรือส่งให้ใครดู และห้ามพิมพ์ใส่ในคำสั่งตรงๆ (ให้กรอกตอนถูกถามในขั้นที่ 7 แทน)
</p>

<h3>ขั้นที่ 2: Init Git ใหม่</h3>
<p>เข้าไปที่โฟลเดอร์โปรเจกต์ของคุณ แล้วเริ่ม Git Repository ใหม่ พร้อมตั้งชื่อ Branch หลักเป็น <code>main</code> ตั้งแต่ต้น (ไม่ต้องมาเปลี่ยนชื่อทีหลังเหมือนตัวอย่างด้านบน):</p>
<pre><code>cd D:\vecskill\devops
git init --initial-branch=main</code></pre>
<p style="font-size: 0.9em; color: #666">(แทนที่ <code>D:\vecskill\devops</code> ด้วย Path จริงของโฟลเดอร์โปรเจกต์คุณ)</p>

<h3>ขั้นที่ 3: เพิ่ม Remote แบบ HTTPS</h3>
<p>บอก Git ว่า Repository ปลายทางบน GitLab อยู่ที่ไหน (ตัวอย่างนี้ Project <code>pes</code> อยู่ภายใต้ Group <code>devops</code>):</p>
<pre><code>git remote add origin https://gitlab.vecskill.ovec/devops/pes.git</code></pre>

<h3>ขั้นที่ 4: เช็คก่อนว่าไฟล์ไหนจะถูกเพิ่ม (กัน <code>.env</code> หลุด)</h3>
<pre><code>git status</code></pre>
<p>
  อ่านรายชื่อไฟล์ที่ขึ้นเป็นสีแดง (Untracked) ให้ละเอียด ตรวจว่าไฟล์ <code>.env</code> <strong>ตัวจริง</strong> (เช่น <code>backend/.env</code>) <strong>ไม่ปรากฏ</strong> ในลิสต์ — ถ้าไม่ขึ้นมาแปลว่าปลอดภัย เพราะถูก
  <code>.gitignore</code> กันไว้แล้ว (ไฟล์ <code>.env.example</code> ที่ไม่มีค่าจริงขึ้นได้ตามปกติ ไม่เป็นไร)
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>ข้อควรรู้เพิ่มเติม:</strong> กฎ <code>.env</code> ใน <code>.gitignore</code> (ไม่มี <code>/</code> นำหน้า) จะกันไฟล์ชื่อ <code>.env</code> ทุกตัวในทุกโฟลเดอร์ย่อย รวมถึง
  <code>deploy/.env</code> ที่ใช้ตอน Deploy ขึ้น Production ตามบท CI/CD ด้วย นั่นหมายความว่าไฟล์นั้น<strong>จะไม่ถูก Commit ขึ้น Git เช่นกัน</strong> ซึ่งเป็นพฤติกรรมที่ถูกต้องและตั้งใจ (ไม่อยากให้ความลับของ
  Production หลุดขึ้น Repository) แต่ก็หมายความว่าต้องมีวิธีส่งไฟล์นั้นไปเครื่อง Production ด้วยช่องทางอื่นแทน เช่น สร้างเองบนเครื่อง Production ครั้งเดียวไว้ก่อน
  หรือให้ Pipeline เขียนไฟล์นี้ขึ้นมาเองจากตัวแปรลับใน GitLab CI/CD Variables ตอน Deploy (ไม่ใช่ดึงจาก Repository)
</p>

<h3>ขั้นที่ 5: Add + Commit</h3>
<pre><code>git add .
git commit -m "Initial commit"</code></pre>

<h3>ขั้นที่ 6: (แนะนำ) ให้ Windows จำ Token ไว้</h3>
<p>ตั้งค่านี้ครั้งเดียว เพื่อให้ Git Credential Manager ของ Windows จำ Token ไว้ให้ ไม่ต้องกรอกซ้ำทุกครั้งที่ Push:</p>
<pre><code class="language-powershell">git config --global credential.helper manager</code></pre>
<p style="font-size: 0.9em; color: #666">(บน macOS ใช้ <code>git config --global credential.helper osxkeychain</code> หรือบน Linux ใช้ <code>git config --global credential.helper store</code> แทน)</p>

<h3>ขั้นที่ 7: Push</h3>
<pre><code>git push --set-upstream origin main</code></pre>
<p>ตอนนี้จะถูกถาม:</p>
<ul>
  <li><strong>Username:</strong> GitLab Username ของคุณ</li>
  <li><strong>Password:</strong> วาง Personal Access Token ที่คัดลอกไว้ตอนขั้นที่ 1 (ไม่ใช่รหัสผ่านบัญชี)</li>
</ul>
<p>หลัง Push สำเร็จครั้งแรก ครั้งต่อไปจะไม่ถามซ้ำอีก เพราะ Credential Manager จำไว้ให้แล้วตามขั้นที่ 6</p>
<div class="image-container">
  <img src="./images/basicgit/basic-git-07-04.png" alt="ตรวจสอบ Repository บน GitLab" class="content-image" />
  <p>ภาพ: ตรวจสอบ Repository บน GitLab</p>
</div>