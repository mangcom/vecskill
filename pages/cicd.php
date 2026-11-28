<h1>การทำระบบ CI/CD ด้วย GitLab</h1>
<p>
  คู่มือนี้พาทำทีละขั้นตอน ตั้งแต่แตกไฟล์โปรเจกต์ รันและทดสอบบนเครื่องตัวเอง เตรียม Server จนถึง Push โค้ดแล้วให้ GitLab ทดสอบ (Test), สร้าง Docker Image (Build and push) และนำขึ้นเครื่องจริง (Deploy) ให้อัตโนมัติ
  โดยใช้โปรเจกต์ <strong>PES</strong> (Backend: Node.js/Express + MySQL, Frontend: Nuxt 3, E2E Test: Playwright) เป็นกรณีศึกษา
</p>

<div style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px; margin: 20px 0">
  <h3 style="margin-top: 0"><i class="fas fa-file-archive"></i> ดาวน์โหลดโปรเจกต์ตัวอย่าง</h3>
  <p style="margin-bottom: 0"><a href="./files/pes-main.zip" download><strong>ดาวน์โหลดไฟล์ pes-main.zip</strong></a> (แตกไฟล์แล้วจะได้โฟลเดอร์ <code>pes-main</code>)</p>
</div>

<div class="image-container">
  <img src="./images/CICD-01.jpg" alt="CI/CD" class="content-image" />
  <p>ภาพรวมการทำงาน: GitLab Runner ทดสอบโค้ด -&gt; Build Image -&gt; Push ขึ้น Registry -&gt; SSH ไป Production -&gt; Pull &amp; Run</p>
</div>

<h2>ภาพรวมสภาพแวดล้อมและ Pipeline</h2>
<table class="spec-table">
  <thead>
    <tr>
      <th>เครื่อง</th>
      <th>IP ที่ใช้ในคู่มือ</th>
      <th>หน้าที่</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Server เครื่องที่ 1</td>
      <td><code>10.20.30.101</code></td>
      <td>GitLab, GitLab Runner, Caddy (HTTPS ด้วย Internal CA), Docker Registry — Container ทั้งหมดอยู่ใน Network <code>app_net</code></td>
    </tr>
    <tr>
      <td>Server เครื่องที่ 2 (Production)</td>
      <td><code>192.168.100.102</code></td>
      <td>รับคำสั่ง Deploy ผ่าน SSH แล้วดึง Image จาก Registry มารันด้วย Docker Compose</td>
    </tr>
    <tr>
      <td>เครื่องนักพัฒนา</td>
      <td>-</td>
      <td>เขียนโค้ด, รันและทดสอบ (รวมถึง E2E Test), Push ขึ้น GitLab</td>
    </tr>
  </tbody>
</table>
<table class="spec-table">
  <thead>
    <tr>
      <th>Stage</th>
      <th>Job</th>
      <th>ทำอะไร</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>test</code></td>
      <td><code>test_backend</code>, <code>test_frontend</code></td>
      <td>รันเทสต์ด้วย Vitest (Backend ต่อกับ MySQL จริงที่สร้างชั่วคราวใน Job)</td>
    </tr>
    <tr>
      <td><code>build and push</code></td>
      <td><code>build_backend</code>, <code>build_frontend</code></td>
      <td>Build Docker Image แบบ Docker-in-Docker แล้ว Push ขึ้น Registry</td>
    </tr>
    <tr>
      <td><code>deploy</code></td>
      <td><code>deploy_prod</code>, <code>stop_prod</code></td>
      <td>ส่งไฟล์ Compose ไป Production แล้วสั่ง Pull และรัน (หรือสั่งหยุดระบบ)</td>
    </tr>
    <tr>
      <td>E2E Test</td>
      <td>รันจากเครื่องนักพัฒนา</td>
      <td>ทดสอบทั้งระบบผ่าน Browser จริงด้วย Playwright (ส่วนที่ 3.4) และมีตัวอย่างเสริมให้รันใน Pipeline (ส่วนที่ 8)</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 0: สร้าง Personal Access Token (สำหรับ Clone/Push ผ่าน HTTPS)</h2>
<p>GitLab ไม่รับรหัสผ่านบัญชีตรงๆ ตอน Clone/Push ผ่าน HTTPS นักพัฒนาแต่ละคนต้องสร้าง <strong>Personal Access Token</strong> ของตัวเองมาใช้แทน</p>
<ol>
  <li>ล็อกอิน <code>https://gitlab.vecskill.ovec</code> แล้วคลิกรูปโปรไฟล์ &gt; <strong>Edit profile</strong></li>
  <li>ไปที่เมนู <strong>Access Tokens</strong></li>
  <li>ตั้งชื่อ เช่น <code>pes-dev-laptop</code>, กำหนดวันหมดอายุ และเลือก Scope <code>read_repository</code> กับ <code>write_repository</code></li>
  <li>กดสร้าง แล้ว <strong>คัดลอกค่าที่ขึ้นต้นด้วย <code>glpat-</code> เก็บไว้ทันที</strong> (แสดงครั้งเดียว) — ดูภาพประกอบได้ในบท <a href="#" onclick="document.querySelector('a[data-page=\'basic-git-usage\']').click();">การใช้งาน Git เบื้องต้น</a></li>
</ol>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>หมายเหตุ:</strong> Token นี้ใช้ตอนคนสั่ง Clone/Push เองเท่านั้น ไม่เกี่ยวกับ Pipeline และห้ามบันทึกลงไฟล์ที่จะ Commit ขึ้น Git
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: รู้จักโปรเจกต์ และค่าที่ต้องแก้ให้ตรงกับระบบของเรา</h2>

<h3>1.1 โครงสร้างโปรเจกต์</h3>
<pre><code>pes-main/
├── .gitlab-ci.yml             # Pipeline: test → build and push → deploy
├── .gitignore                 # กัน .env, .env.production, node_modules และผลเทสต์ไม่ให้ขึ้น Git
├── .env.example               # ค่าตั้งต้นสำหรับรันบนเครื่องพัฒนา (คัดลอกเป็น .env)
├── .env.production.example    # ตัวอย่างค่าฐานข้อมูลบน Production
├── docker-compose.yml         # รันบนเครื่องพัฒนา (Build จาก Source Code)
├── docker-compose_mysql.yml   # ทางเลือก: รันเฉพาะ MySQL + phpMyAdmin
├── docker-compose.prod.yml    # รันบน Production (ดึง Image จาก Registry)
├── README.md                  # การใช้งานโปรเจกต์
├── schema.sql                 # โครงสร้างฐานข้อมูล + ข้อมูลตัวอย่าง (ใช้ร่วมกันทั้ง Dev, CI และ Production)
├── deploy/
│   └── .env.example           # ตัวอย่างค่าของ Backend บน Production
├── backend/                   # Express API (พอร์ต 7000)
│   ├── Dockerfile
│   └── test/                  # Vitest + Supertest
└── frontend/                  # Nuxt 3 (พอร์ต 3000)
    ├── Dockerfile
    ├── tests/                 # Component Test (Vitest)
    ├── e2e/                   # E2E Test (Playwright)
    └── playwright.config.ts</code></pre>
<h3>1.2 ค่าที่ตั้งไว้ในโปรเจกต์ (แก้เมื่อระบบของคุณใช้ค่าอื่น)</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ค่า</th>
      <th>ตั้งไว้เป็น</th>
      <th>แก้ที่</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>เครื่อง Production</td>
      <td><code>192.168.100.102</code></td>
      <td><code>.gitlab-ci.yml</code> : <code>DEPLOY_HOST</code></td>
    </tr>
    <tr>
      <td>Server 1</td>
      <td><code>10.20.30.101</code></td>
      <td><code>/etc/hosts</code> บน Production (ส่วนที่ 4.3)</td>
    </tr>
    <tr>
      <td>URL ของ Frontend ที่ API ยอมรับ</td>
      <td><code>http://192.168.100.102:3000</code></td>
      <td><code>deploy/.env</code> : <code>CORS_ORIGIN</code> (ส่วนที่ 4.5)</td>
    </tr>
    <tr>
      <td>URL ของ API ที่ Browser เรียก</td>
      <td><code>http://192.168.100.102:7000</code></td>
      <td><code>.env.production</code> และค่าเริ่มต้นใน <code>docker-compose.prod.yml</code> (ตอน Deploy นั้น Pipeline สร้างจาก <code>DEPLOY_HOST</code>)</td>
    </tr>
    <tr>
      <td>Registry สำหรับ Build/Push</td>
      <td><code>registry:5000</code></td>
      <td>CI/CD Variable <code>CI_REGISTRY</code> และ <code>--insecure-registry</code> ใน <code>.gitlab-ci.yml</code></td>
    </tr>
    <tr>
      <td>Registry สำหรับ Production</td>
      <td><code>registry.vecskill.ovec</code></td>
      <td><code>.env.production</code> : <code>CI_REGISTRY</code> (และค่าเริ่มต้นใน <code>docker-compose.prod.yml</code>)</td>
    </tr>
    <tr>
      <td>โดเมนที่เปิดหน้าเว็บได้</td>
      <td><code>.vecskill.ovec</code> (รวมถึง localhost และ IP)</td>
      <td><code>frontend/nuxt.config.ts</code> : <code>allowedHosts</code></td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: รันโปรเจกต์บนเครื่องพัฒนา (Local)</h2>
<p>ต้องมี Docker (ดูบท <a href="#" onclick="document.querySelector('a[data-page=\'docker\']').click();">การติดตั้ง Docker</a>) และรันคำสั่งในโฟลเดอร์ <code>pes-main</code></p>

<h3>2.1 สร้าง Network และไฟล์ .env</h3>
<p>ไฟล์ Compose ใช้ Network ภายนอกชื่อ <code>app_net</code> (ถ้ามีอยู่แล้วคำสั่งแรกจะแจ้ง Error ข้ามได้):</p>
<pre><code class="language-powershell">docker network create app_net
cp .env.example .env</code></pre>
<p>เปิดไฟล์ <code>.env</code> แล้วเปลี่ยน <code>JWT_SECRET</code> ค่าอื่นใช้ค่าเริ่มต้นได้ ส่วน <code>NUXT_PUBLIC_API_BASE</code> ปล่อยว่างไว้ (Frontend จะเรียก Backend ที่ Host เดียวกับที่เปิดเว็บ พอร์ต 7000 ให้เอง)</p>

<h3>2.2 Build และรันทั้งระบบ</h3>
<pre><code>docker compose up -d --build
docker compose ps</code></pre>
<table class="spec-table">
  <thead>
    <tr>
      <th>บริการ</th>
      <th>URL</th>
      <th>หมายเหตุ</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Frontend</td>
      <td><code>http://localhost:3000</code></td>
      <td>หน้าแรกที่เปิดอาจช้า เพราะ Nuxt Dev Server คอมไพล์ตอนมีคนเรียก</td>
    </tr>
    <tr>
      <td>Backend API + Swagger</td>
      <td><code>http://localhost:7000/docs</code></td>
      <td>ตรวจสถานะได้ที่ <code>/health</code></td>
    </tr>
    <tr>
      <td>phpMyAdmin</td>
      <td><code>http://localhost:8080</code></td>
      <td><code>root</code> / <code>rootpassword</code></td>
    </tr>
    <tr>
      <td>MySQL</td>
      <td><code>localhost:3306</code></td>
      <td>ถ้าชนกับ MySQL ของ XAMPP ให้แก้ใน <code>docker-compose.yml</code> เป็น <code>"3307:3306"</code></td>
    </tr>
  </tbody>
</table>
<p><strong>บัญชีตัวอย่าง</strong> (รหัสผ่าน <code>12345678</code> ทุกบัญชี): <code>admin@vecskill.ovec</code> (ผู้ดูแล), <code>eva.it@vecskill.ovec</code> (กรรมการ), <code>t.it01@vecskill.ovec</code> (ผู้รับการประเมิน)</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>รอบแรกใช้เวลานานกว่าปกติ:</strong> <code>backend</code> จะรอจน <code>db</code> เป็น <code>healthy</code> คือ MySQL โหลด <code>schema.sql</code> เสร็จก่อน ดูความคืบหน้าได้ด้วย <code>docker compose logs -f db</code>
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: การทดสอบ 3 ระดับ</h2>
<table class="spec-table">
  <thead>
    <tr>
      <th>ระดับ</th>
      <th>เครื่องมือ / โฟลเดอร์</th>
      <th>ต้องมีอะไรรันอยู่</th>
      <th>ผลจากการทดสอบไฟล์ในโปรเจกต์</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Backend (Unit + Integration)</td>
      <td>Vitest + Supertest / <code>backend/test/</code></td>
      <td>MySQL ที่มีข้อมูลจาก <code>schema.sql</code></td>
      <td>ผ่าน 22 เทสต์ (8 ไฟล์)</td>
    </tr>
    <tr>
      <td>Frontend (Component)</td>
      <td>Vitest + Vue Test Utils / <code>frontend/tests/</code></td>
      <td>ไม่ต้องมี (จำลอง API)</td>
      <td>ผ่าน 5 เทสต์ (2 ไฟล์)</td>
    </tr>
    <tr>
      <td>E2E (End-to-End)</td>
      <td>Playwright / <code>frontend/e2e/</code></td>
      <td>ทั้งระบบ: Frontend + Backend + ฐานข้อมูล</td>
      <td>ผ่าน 3 เทสต์ (2 ไฟล์)</td>
    </tr>
  </tbody>
</table>

<h3>3.1 รันเทสต์ Backend</h3>
<p>หลังจากรันระบบตามส่วนที่ 2 แล้ว สั่งเทสต์ใน Container ได้เลย (ไม่ต้องติดตั้ง Node.js บนเครื่อง):</p>
<pre><code>docker compose exec backend npm test</code></pre>

<h3>3.2 รันเทสต์ Frontend</h3>
<pre><code>docker compose exec frontend npm test</code></pre>

<h3>3.3 E2E Test คืออะไร และมีกรณีทดสอบอะไรบ้าง</h3>
<p>E2E Test เปิด Browser จริง แล้วคลิกและพิมพ์เหมือนผู้ใช้ เพื่อพิสูจน์ว่าทุกส่วนทำงานร่วมกันได้ตั้งแต่ต้นจนจบ Workflow</p>
<table class="spec-table">
  <thead>
    <tr>
      <th>รหัส</th>
      <th>ขั้นตอน</th>
      <th>ผลที่คาดหวัง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>E2E-001</td>
      <td>เปิดหน้า Login &gt; กรอกบัญชี <code>admin</code> &gt; กด Sign in</td>
      <td>กลับไปหน้าแรก เห็นปุ่ม Logout และอีเมลผู้ใช้ในตาราง</td>
    </tr>
    <tr>
      <td>E2E-001b</td>
      <td>Login ด้วยรหัสผ่านผิด</td>
      <td>แสดง <code>Invalid credentials-password</code> และยังอยู่หน้า Login</td>
    </tr>
    <tr>
      <td>E2E-002</td>
      <td>Login ด้วย <code>t.it01</code> &gt; หน้า Upload &gt; เลือกตัวชี้วัดและชนิดหลักฐาน &gt; แนบไฟล์ &gt; กด Upload</td>
      <td>แสดงข้อความ <code>อัปโหลดสำเร็จ</code> แล้วเทสต์ลบรายการที่สร้างทิ้ง</td>
    </tr>
  </tbody>
</table>
<p>ตัวอย่างโค้ด E2E-001 และ E2E-001b จาก <code>frontend/e2e/login.e2e.spec.ts</code> (ตัดคอมเมนต์ออก):</p>
<pre><code class="language-typescript">import { test, expect } from '@playwright/test'

test('E2E-001: ผู้ใช้เข้าสู่ระบบสำเร็จแล้วเห็นหน้า Dashboard', async ({ page }) =&gt; {
  await page.goto('/login')

  await page.getByLabel('Email').first().fill('admin@vecskill.ovec')
  await page.getByLabel('Password').first().fill('12345678')

  await page.getByRole('button', { name: 'Sign in' }).click()

  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByRole('button', { name: 'Logout' })).toBeVisible()
  await expect(page.getByText('admin@vecskill.ovec')).toBeVisible()
})

test('E2E-001b (กรณีผิด): กรอกรหัสผ่านผิด ระบบต้องไม่พาเข้าสู่ระบบ', async ({ page }) =&gt; {
  await page.goto('/login')
  await page.getByLabel('Email').first().fill('admin@vecskill.ovec')
  await page.getByLabel('Password').first().fill('wrong-password')
  await page.getByRole('button', { name: 'Sign in' }).click()

  await expect(page.getByText('Invalid credentials-password')).toBeVisible()
  await expect(page).toHaveURL(/\/login$/)
})</code></pre>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>เทคนิคกับ Vuetify:</strong> <code>getByLabel('Password')</code> ตรงกับหลาย Element (รวมไอคอนรูปตา) จึงต้องต่อด้วย <code>.first()</code> ส่วน Dropdown (<code>v-select</code>) ในเทสต์ E2E-002 ใช้
  <code>page.locator('.v-field', { hasText: '...' })</code> แทนการหาด้วย Label
</p>

<h3>3.4 รัน E2E Test จากเครื่องนักพัฒนา</h3>
<p>ต้องมี Node.js 20–22 บนเครื่อง (ดูบท <a href="#" onclick="document.querySelector('a[data-page=\'nodejs-install\']').click();">การติดตั้ง Node.js</a>) และระบบจากส่วนที่ 2 ต้องรันอยู่ ครั้งแรกต้องติดตั้ง Package และ Browser (ใช้อินเทอร์เน็ต ในสนามแข่งขันควรติดตั้งไว้ล่วงหน้า):</p>
<pre><code class="language-powershell">cd frontend
npm ci
npx playwright install chromium</code></pre>
<p>รันเทสต์:</p>
<pre><code>npm run test:e2e</code></pre>
<p>ตัวอย่างผลลัพธ์เมื่อผ่านทั้งหมด (เวลาของแต่ละเครื่องไม่เท่ากัน):</p>
<pre><code>Running 3 tests using 1 worker

  ok 1 [chromium] › e2e\login.e2e.spec.ts › E2E-001: ผู้ใช้เข้าสู่ระบบสำเร็จแล้วเห็นหน้า Dashboard (21.0s)
  ok 2 [chromium] › e2e\login.e2e.spec.ts › E2E-001b (กรณีผิด): กรอกรหัสผ่านผิด ระบบต้องไม่พาเข้าสู่ระบบ (4.5s)
  ok 3 [chromium] › e2e\upload.e2e.spec.ts › E2E-002: ผู้ใช้ login แล้วอัปโหลดไฟล์หลักฐานสำเร็จ (10.3s)

  3 passed (37.8s)</code></pre>
<p>คำสั่งช่วยตรวจสอบเมื่อเทสต์ไม่ผ่าน:</p>
<pre><code class="language-powershell"># ดูการทำงานทีละขั้นผ่านหน้าต่าง Playwright UI
npm run test:e2e:ui

# ลองใหม่อัตโนมัติ 1 ครั้ง (ช่วยกรณีหน้าแรกคอมไพล์ช้าจนเกินเวลา 30 วินาที)
npm run test:e2e -- --retries=1

# เปิดดู Trace ของเทสต์ที่ไม่ผ่าน (แทนชื่อโฟลเดอร์ตามที่แสดงในผลเทสต์)
npx playwright show-trace test-results/ชื่อโฟลเดอร์/trace.zip</code></pre>
<p>ทดสอบระบบที่อยู่เครื่องอื่น (เช่น หลัง Deploy ขึ้น Production) ให้กำหนด <code>E2E_BASE_URL</code>:</p>
<pre><code class="language-powershell"># Windows PowerShell
$env:E2E_BASE_URL="http://192.168.100.102:3000"; npm run test:e2e</code></pre>
<pre><code class="language-bash"># Linux / macOS / Git Bash
E2E_BASE_URL=http://192.168.100.102:3000 npm run test:e2e</code></pre>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ข้อควรระวังของ E2E-002:</strong><br />
  1) คำสั่งลบข้อมูลหลังเทสต์ใน <code>upload.e2e.spec.ts</code> ระบุพอร์ต <code>7000</code> ไว้ตายตัว ถ้าเปลี่ยน <code>BACKEND_PORT</code> ต้องแก้บรรทัดนั้นด้วย<br />
  2) ระบบลบเฉพาะรายการในฐานข้อมูล ไฟล์ <code>e2e-evidence.png</code> ยังค้างในโฟลเดอร์ <code>uploads</code><br />
  3) เมื่อชี้ไปที่ Production เทสต์จะเขียนข้อมูลจริง และใช้บัญชีตัวอย่างจาก <code>schema.sql</code> (ถ้าเปลี่ยนรหัสผ่านแล้ว เทสต์จะไม่ผ่าน)
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: เตรียม Server (ทำครั้งเดียว ก่อน Push โค้ด)</h2>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>ทำส่วนนี้และส่วนที่ 5 ให้ครบก่อน Push:</strong> ไฟล์ใน zip ตั้ง <code>AUTO_DEPLOY: "true"</code> ไว้ การ Push เข้า <code>main</code> ครั้งแรกจึง Deploy ทันที
</p>

<h3>4.1 Server 1: ตรวจ GitLab Runner และ Registry</h3>
<p>ติดตั้งตามบท <a href="#" onclick="document.querySelector('a[data-page=\'runner\']').click();">การติดตั้ง Gitlab Runner</a> และ <a href="#" onclick="document.querySelector('a[data-page=\'registry\']').click();">การติดตั้ง Registry &amp; UI</a> แล้วตรวจ <code>config.toml</code> ส่วน <code>[runners.docker]</code> ให้เป็นดังนี้:</p>
<pre><code class="language-toml">  [runners.docker]
    tls_verify = false
    image = "docker:24.0.5"
    privileged = true                  # จำเป็นสำหรับ Docker-in-Docker
    volumes = ["/cache"]               # ต้องไม่มี /var/run/docker.sock
    network_mode = "app_net"           # ให้ Job มองเห็น gitlab และ registry
    pull_policy = ["if-not-present"]</code></pre>
<p>ถ้ายังมี <code>"/var/run/docker.sock:/var/run/docker.sock"</code> อยู่ใน <code>volumes</code> Service <code>docker:26-dind</code> จะสร้าง Socket ของตัวเองไม่ได้ (<code>device or resource busy</code>) เมื่อแก้แล้วให้ Restart <strong>ที่เครื่อง Host</strong>:</p>
<pre><code>docker restart gitlab-runner</code></pre>
<p>ตรวจว่า Container ชื่อ <code>gitlab</code>, <code>gitlab-runner</code>, <code>registry</code> และ <code>caddy</code> อยู่ใน Network เดียวกัน:</p>
<pre><code>docker network inspect app_net --format '{{range .Containers}}{{.Name}} {{end}}'</code></pre>

<h3>4.2 Production: Docker, User <code>devops</code> และ Network</h3>
<p>ติดตั้ง Docker ตามบท <a href="#" onclick="document.querySelector('a[data-page=\'docker\']').click();">การติดตั้ง Docker</a> (ถ้ายังไม่มี User <code>devops</code> ให้สร้างด้วย <code>sudo adduser devops</code>) แล้วรัน:</p>
<pre><code class="language-bash">sudo usermod -aG docker devops
sudo docker network create app_net
sudo apt install -y rsync</code></pre>
<p>จากนั้นเปิด <strong>NOPASSWD sudo</strong> ให้ <code>devops</code> ตามบท <a href="#" onclick="document.querySelector('a[data-page=\'ubuntu-setup\']').click();">การเตรียม Ubuntu Server</a> หัวข้อ "6. การใช้ sudo โดยไม่ต้องใส่รหัสผ่าน" เพราะ Job <code>deploy_prod</code> สั่ง <code>sudo mkdir</code> และ <code>sudo chown</code> ผ่าน SSH โดยไม่มีคนพิมพ์รหัสผ่าน ต้อง Logout แล้ว Login ใหม่ เพื่อให้สิทธิ์กลุ่ม <code>docker</code> มีผล และทดสอบ:</p>
<pre><code class="language-bash">docker ps
sudo -n true &amp;&amp; echo "sudo OK"</code></pre>

<h3>4.3 Production: ให้รู้จักและเชื่อถือ Registry</h3>
<p>Production ดึง Image ผ่าน <code>https://registry.vecskill.ovec</code> (ผ่าน Caddy ไม่มีพอร์ต) จึงต้องทำครบ 3 อย่าง: เชื่อมต่อถึงกันได้, รู้จักชื่อโดเมน และเชื่อถือ Root CA ของ Caddy (ต้องดึงไฟล์ Root CA ออกมาตามขั้นตอนที่ 3.1 ในบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ก่อน)</p>
<pre><code class="language-bash"># 1) ทดสอบว่าเครื่องคนละวง Network เชื่อมถึงกันได้ (ถ้าไม่ผ่านให้ตรวจ Routing/Firewall ก่อน)
nc -zv 10.20.30.101 443

# 2) ชี้ชื่อโดเมนไปที่ Server 1 (ถ้ามี DNS เช่น Technitium ที่ตั้งไว้แล้ว ข้ามข้อนี้ได้)
echo "10.20.30.101 registry.vecskill.ovec gitlab.vecskill.ovec" | sudo tee -a /etc/hosts

# 3) ติดตั้ง Root CA ของ Caddy แล้ว Restart Docker ให้อ่านใบรับรองใหม่
scp devops@10.20.30.101:/opt/caddy/caddy-root.crt /tmp/caddy-root.crt
sudo cp /tmp/caddy-root.crt /usr/local/share/ca-certificates/caddy-root.crt
sudo update-ca-certificates
sudo systemctl restart docker

# 4) ทดสอบ: ต้องได้ผลลัพธ์เป็น JSON โดยไม่มี Error เรื่อง Certificate
curl https://registry.vecskill.ovec/v2/_catalog</code></pre>
<p>และจาก Server 1 ต้อง SSH ไปหา Production ได้:</p>
<pre><code>nc -zv 192.168.100.102 22</code></pre>

<h3>4.4 สร้าง SSH Key สำหรับ Deploy (บนเครื่อง Production)</h3>
<pre><code class="language-bash">sudo -u devops mkdir -p /home/devops/.ssh
sudo -u devops ssh-keygen -t ed25519 -f /home/devops/.ssh/deploy_key -N "" -C "gitlab-ci-deploy"
sudo -u devops sh -c 'cat /home/devops/.ssh/deploy_key.pub &gt;&gt; /home/devops/.ssh/authorized_keys'
sudo chmod 700 /home/devops/.ssh
sudo chmod 600 /home/devops/.ssh/authorized_keys

# แสดง Private Key เพื่อนำไปใส่ใน GitLab (ส่วนที่ 5)
sudo cat /home/devops/.ssh/deploy_key</code></pre>
<p>คัดลอกทั้งหมด รวมบรรทัด <code>BEGIN OPENSSH PRIVATE KEY</code> และ <code>END OPENSSH PRIVATE KEY</code> (ห้ามใช้ Host Key ใน <code>/etc/ssh/</code> แทน)</p>

<h3>4.5 สร้างไฟล์ค่าลับ 2 ไฟล์บน Production</h3>
<p>เนื้อหาด้านล่างตรงกับไฟล์ <code>.env.production.example</code> และ <code>deploy/.env.example</code> ในโปรเจกต์ ให้แทนค่า <code>__CHANGE_ME_..__</code> ทุกตำแหน่ง ไฟล์เหล่านี้<strong>สร้างด้วยมือครั้งเดียว</strong> ไม่ขึ้น Git และ Pipeline ไม่เขียนทับ ถ้าไม่มีไฟล์ <code>deploy/.env</code> คำสั่ง <code>docker compose</code> ตอน Deploy จะหยุดด้วย <code>env file ... not found</code></p>
<table class="spec-table">
  <thead>
    <tr>
      <th>ไฟล์</th>
      <th>ใครอ่าน</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>/opt/vecskill/.env.production</code></td>
      <td>Docker Compose (<code>--env-file</code>) ใช้แทนค่า <code>${...}</code> ใน <code>docker-compose.prod.yml</code></td>
    </tr>
    <tr>
      <td><code>/opt/vecskill/deploy/.env</code></td>
      <td>Container <code>backend</code> (ผ่าน <code>env_file:</code>)</td>
    </tr>
  </tbody>
</table>
<pre><code class="language-bash">sudo mkdir -p /opt/vecskill/deploy
sudo chown -R devops:devops /opt/vecskill
nano /opt/vecskill/.env.production</code></pre>
<pre><code class="language-ini"># ===================================================================
# ค่าตั้งค่าของ Docker Compose บนเครื่อง "Production"
# ===================================================================
# วิธีใช้: สร้างไฟล์ /opt/vecskill/.env.production บนเครื่อง Production ด้วยมือ
# ครั้งเดียว (คัดลอกเนื้อหานี้ไป) ก่อน push โค้ดขึ้น main ครั้งแรก แล้วจำกัดสิทธิ์ไฟล์
#     chmod 600 /opt/vecskill/.env.production
# Docker Compose อ่านไฟล์นี้ผ่าน --env-file เพื่อแทนค่า ${...} ใน docker-compose.prod.yml
#
# ข้อควรระวัง
#   - ห้าม commit ไฟล์ที่กรอกค่าจริงแล้ว
#   - แทนค่า __CHANGE_ME_..__ ทุกตำแหน่ง และไม่ควรใช้อักขระ $ ในรหัสผ่าน
#   - รหัสผ่านฐานข้อมูลถูกตั้งตอนสร้างฐานข้อมูลครั้งแรกเท่านั้น การแก้ไฟล์ภายหลัง
#     ไม่เปลี่ยนรหัสผ่านใน MySQL ให้


# ================= Registry และเวอร์ชันของ image =====================

# CI_REGISTRY = ที่อยู่ของ Registry ที่เครื่อง Production ใช้ดึง image
#   registry.vecskill.ovec      = ผ่าน HTTPS (Caddy) ไม่ต้องใส่พอร์ต (ค่าที่คู่มือใช้)
#   registry.vecskill.ovec:5000 = แบบไม่เข้ารหัส (insecure) ต้องตั้ง insecure-registries
#                                 ใน /etc/docker/daemon.json บนเครื่อง Production ด้วย
# Pipeline ไม่ได้ส่งค่านี้ไป ค่าในไฟล์นี้จึงมีผลทั้งตอน deploy ผ่าน Pipeline และสั่งเอง
CI_REGISTRY=registry.vecskill.ovec

# IMAGE_TAG = เวอร์ชัน (tag) ของ image ที่ต้องการรัน เช่น latest หรือรหัส commit
# ตอน deploy ผ่าน Pipeline จะใช้ latest เสมอ ค่าในไฟล์นี้มีผลเมื่อสั่ง docker compose
# ด้วยมือ (Registry ต้องมี tag นั้นทั้ง image ของ backend และ frontend)
IMAGE_TAG=latest


# ========================= ฐานข้อมูล (MySQL) =========================

# รหัสผ่านของผู้ดูแลฐานข้อมูล (root) ใช้เข้า phpMyAdmin ด้วย
MYSQL_ROOT_PASSWORD=__CHANGE_ME_ROOT__

# ชื่อฐานข้อมูล ต้องตรงกับ schema.sql และ DB_NAME ใน deploy/.env
MYSQL_DATABASE=skills_db

# บัญชีที่ backend ใช้เข้าฐานข้อมูล ต้องตรงกับ DB_USER ใน deploy/.env
MYSQL_USER=user1

# รหัสผ่านของบัญชีข้างบน ต้องตรงกับ DB_PASS ใน deploy/.env
MYSQL_PASSWORD=__CHANGE_ME_USER__


# ============================= หน้าเว็บ ==============================

# URL ของ Backend ที่เบราว์เซอร์ของผู้ใช้เรียกถึงได้ ใช้ IP หรือโดเมนของเครื่อง
# Production (ห้ามใช้ localhost หรือชื่อ service "backend")
# ตอน deploy ผ่าน Pipeline ค่านี้ถูกแทนด้วย http://&lt;DEPLOY_HOST&gt;:7000 อัตโนมัติ
# ค่าในไฟล์นี้มีผลเมื่อสั่ง docker compose ด้วยมือบนเครื่อง Production
NUXT_PUBLIC_API_BASE=http://192.168.100.102:7000</code></pre>
<pre><code>nano /opt/vecskill/deploy/.env</code></pre>
<pre><code class="language-ini"># ===================================================================
# ค่าตั้งค่าของ Backend บนเครื่อง "Production"
# ===================================================================
# วิธีใช้: สร้างไฟล์ /opt/vecskill/deploy/.env บนเครื่อง Production ด้วยมือครั้งเดียว
# ก่อน push โค้ดขึ้น main ครั้งแรก แล้วจำกัดสิทธิ์ไฟล์
#     chmod 600 /opt/vecskill/deploy/.env
# container backend อ่านไฟล์นี้ (env_file ใน docker-compose.prod.yml)
#
# ข้อควรระวัง
#   - ห้าม commit ไฟล์ที่กรอกค่าจริงแล้ว
#   - แก้ไฟล์แล้วต้องสั่งคำสั่งนี้ในโฟลเดอร์ /opt/vecskill (restart เฉย ๆ ไม่อ่านค่าใหม่)
#     docker compose -f docker-compose.prod.yml --env-file .env.production up -d backend

# พอร์ตที่ backend เปิดรับภายใน container ต้องเป็น 7000 ให้ตรงกับ ports ใน
# docker-compose.prod.yml
PORT=7000

# URL ของหน้าเว็บที่อนุญาตให้เรียก API
# ต้องตรงกับที่อยู่บนเบราว์เซอร์ทุกตัวอักษร (ไม่มี / ต่อท้าย) ไม่งั้นหน้าเว็บขึ้น
# Network Error  ใส่หลายค่าได้โดยคั่นด้วยเครื่องหมาย , ไม่เว้นวรรค เช่น
#   CORS_ORIGIN=http://192.168.100.102:3000,http://apps.vecskill.ovec:3000
# ถ้าเว้นว่าง = อนุญาตทุกที่ (ไม่แนะนำบน Production)
CORS_ORIGIN=http://192.168.100.102:3000


# ========================= ฐานข้อมูล (MySQL) =========================
# ไม่ต้องกำหนด DB_HOST เพราะ docker-compose.prod.yml ตั้งเป็น "db" ให้แล้ว

# พอร์ตของ MySQL ภายใน network (ปกติไม่ต้องแก้)
DB_PORT=3306
# ต้องตรงกับ MYSQL_USER ใน .env.production
DB_USER=user1
# ต้องตรงกับ MYSQL_PASSWORD ใน .env.production
DB_PASS=__CHANGE_ME_USER__
# ต้องตรงกับ MYSQL_DATABASE ใน .env.production
DB_NAME=skills_db


# ======================== ความปลอดภัย (Login) ========================

# กุญแจลับสำหรับเซ็น token ตอน login ใช้ข้อความสุ่มยาว ๆ และไม่ซ้ำกับเครื่องพัฒนา
# สร้างได้ด้วย: openssl rand -hex 32
JWT_SECRET=__CHANGE_ME_JWT__
# อายุของ token เช่น 30m = 30 นาที, 2h = 2 ชั่วโมง, 7d = 7 วัน
JWT_EXPIRES=2h


# =========================== ตัวเลือกเสริม ===========================
# UPLOAD_MAX_MB = เพดานขนาดไฟล์อัปโหลดฝั่ง Backend หน่วยเป็น MB (ถ้าไม่กำหนดคือ 10)
# หน้า Upload บนเว็บจำกัดไว้ที่ 10 MB อีกชั้นหนึ่ง (ค่า max_mb ใน
# backend/routes/attachments.js) ดังนั้น
#   - ตั้งต่ำกว่า 10 : Backend ปฏิเสธไฟล์ที่ใหญ่เกินค่านี้
#   - ตั้งสูงกว่า 10 : หน้าเว็บยังรับได้ไม่เกิน 10 MB ต้องแก้ attachments.js ด้วย
# ลบเครื่องหมาย # หน้าบรรทัดล่างเมื่อต้องการกำหนดเอง
# UPLOAD_MAX_MB=10</code></pre>
<pre><code class="language-bash"># สร้างค่าสุ่มสำหรับ JWT_SECRET
openssl rand -hex 32

chmod 600 /opt/vecskill/.env.production /opt/vecskill/deploy/.env</code></pre>
<ul>
  <li><code>DB_PASS</code> ต้องเป็นค่าเดียวกับ <code>MYSQL_PASSWORD</code> และไม่ควรใช้อักขระ <code>$</code> ในรหัสผ่าน</li>
  <li><code>CORS_ORIGIN</code> ต้องตรงกับ URL ที่ Browser ใช้เปิด Frontend ไม่งั้นจะขึ้น <code>Network Error</code> ทั้งที่ API ทำงานปกติ</li>
  <li><code>NUXT_PUBLIC_API_BASE</code> คือ URL ของ API ที่ Browser ของผู้ใช้ต้องเรียกถึงได้ ใช้เมื่อสั่ง <code>docker compose up</code> ด้วยมือบน Production</li>
  <li>รหัสผ่านฐานข้อมูลถูกใช้ตอนสร้าง Volume <code>db_data</code> ครั้งแรกเท่านั้น การแก้ไฟล์ภายหลังไม่เปลี่ยนรหัสผ่านใน MySQL ให้</li>
</ul>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>กรณีสนามแข่งขันไม่มีอินเทอร์เน็ต:</strong> Pipeline นี้ต้องมี Image <code>node:20-alpine</code>, <code>mysql:8.0</code>, <code>docker:26</code>, <code>docker:26-dind</code>, <code>alpine:3.20</code> บน Runner และ <code>mysql:8.0</code>, <code>phpmyadmin</code> บน Production
  รวมถึงใช้ <code>apk add</code> และ <code>npm ci</code> ซึ่งต้องเข้าถึง Alpine Repository และ NPM Registry ได้ จึงต้องเตรียม Mirror หรือดึง Image ไว้ล่วงหน้า (ดูหมวด Local Mirror และบท <a href="#" onclick="document.querySelector('a[data-page=\'verdaccio\']').click();">Verdaccio</a>) และปรับค่าใน Pipeline/Dockerfile ให้ชี้ไปที่ Mirror
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 5: ตั้งค่า GitLab (Group, Project และ CI/CD Variables)</h2>
<ol>
  <li>สร้าง Group ชื่อ <code>devops</code> แล้วสร้าง <strong>Blank project</strong> ชื่อ <code>pes</code> ในกลุ่มนั้น โดย<strong>ไม่ติ๊ก</strong> Initialize repository with a README</li>
  <li>ไปที่ Project &gt; <strong>Settings</strong> &gt; <strong>General</strong> &gt; <strong>Visibility, project features, permissions</strong> แล้วปิด <strong>Container registry</strong> (เราใช้ Registry ของเราเอง ถ้าเปิดไว้ GitLab จะใส่ตัวแปร <code>CI_REGISTRY_*</code> ของมันให้ทุก Job)</li>
  <li>ไปที่ <strong>Settings</strong> &gt; <strong>CI/CD</strong> &gt; <strong>Variables</strong> &gt; <strong>Add variable</strong> (ตั้งที่ Project หรือที่ Group <code>devops</code> ก็ได้ ถ้าต้องการใช้ร่วมหลาย Project):</li>
</ol>
<table class="spec-table">
  <thead>
    <tr>
      <th>Key</th>
      <th>Value</th>
      <th>การตั้งค่า</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>CI_REGISTRY</code></td>
      <td><code>registry:5000</code></td>
      <td>Visible, ไม่ติ๊ก Protect</td>
    </tr>
    <tr>
      <td><code>DEPLOY_PRIV_KEY</code></td>
      <td>Private Key จากส่วนที่ 4.4</td>
      <td>Type = Variable, Visible (ใช้ Masked ไม่ได้เพราะมีหลายบรรทัด) — สำหรับสนามฝึก/แข่งขันไม่ติ๊ก Protect ส่วนระบบใช้งานจริงควรติ๊ก <strong>Protect variable</strong> ให้ใช้ได้เฉพาะ Protected Branch</td>
    </tr>
    <tr>
      <td><code>AUTO_DEPLOY</code> (ไม่บังคับ)</td>
      <td><code>false</code></td>
      <td>ใส่เมื่อต้องการให้กดปุ่ม Play ก่อน Deploy (ค่าในไฟล์คือ <code>true</code>)</td>
    </tr>
  </tbody>
</table>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>ทำไม <code>CI_REGISTRY</code> เป็น <code>registry:5000</code>:</strong> Job Build ทำงานใน Network <code>app_net</code> จึงเรียก Container ด้วยชื่อสั้นได้ โดยไม่ต้องพึ่ง DNS หรือ Certificate ส่วน Production อยู่คนละเครื่อง Pipeline จึง<strong>ไม่ส่ง</strong>ค่านี้ไป ปล่อยให้
  เครื่อง Production ใช้ค่า <code>CI_REGISTRY</code> จาก <code>.env.production</code> หรือค่าเริ่มต้นใน <code>docker-compose.prod.yml</code> ซึ่งทั้งสองที่ตั้งเป็น <code>registry.vecskill.ovec</code> (HTTPS) — เป็น Registry ตัวเดียวกัน เข้าคนละทาง<br />
  ถ้า Registry อยู่<strong>คนละเครื่อง</strong>กับ Runner ให้ใช้ <code>IP_ของ_Registry:5000</code> แทน และแก้ <code>--insecure-registry</code> ใน <code>.gitlab-ci.yml</code> ให้ตรงกัน
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 6: ไฟล์ที่ Pipeline ใช้</h2>

<h3>6.1 Dockerfile (ใช้ตามไฟล์ใน zip ไม่ต้องแก้)</h3>
<p><strong><code>backend/Dockerfile</code></strong></p>
<pre><code class="language-dockerfile">FROM node:20-alpine
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
EXPOSE 7000
CMD ["npm","start"]</code></pre>
<p><strong><code>frontend/Dockerfile</code></strong></p>
<pre><code class="language-dockerfile">FROM node:20-alpine
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
EXPOSE 3000
CMD ["npm","run","dev"]</code></pre>
<p style="font-size: 0.9em; color: #666">Frontend บน Production จึงรันแบบ Dev Server (<code>npm run dev</code>) ในโปรเจกต์มี <code>frontend/Dockerfile.prod</code> แบบ Multi-stage ให้ด้วย แต่ Pipeline ยังไม่ได้เรียกใช้ และคู่มือนี้ยังไม่ได้ทดสอบไฟล์ดังกล่าว</p>

<h3>6.2 docker-compose.prod.yml</h3>
<pre><code class="language-yaml">services:
  db:
    image: mysql:8.0
    command: &gt;
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE:-skills_db}
      MYSQL_USER: ${MYSQL_USER:-user1}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
      TZ: Asia/Bangkok
    volumes:
      - db_data:/var/lib/mysql
      # ทำงานเฉพาะตอนสร้างฐานข้อมูลครั้งแรก (Volume ว่าง)
      - ./deploy/schema.sql:/docker-entrypoint-initdb.d/schema.sql:ro
    healthcheck:
      # ตรวจผ่าน TCP ด้วย -h 127.0.0.1 (ดูคำอธิบายด้านล่าง)
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-u", "root", "-p${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 60s
    restart: unless-stopped
    networks: [app_net]

  # ไม่รันเอง ต้องสั่งด้วย --profile tools
  phpmyadmin:
    image: phpmyadmin:latest
    profiles: ["tools"]
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    ports:
      - "8080:80"
    depends_on:
      - db
    restart: unless-stopped
    networks: [app_net]

  backend:
    # Pipeline ไม่ได้ส่ง CI_REGISTRY มา จึงใช้ค่าจาก .env.production หรือค่าเริ่มต้น (HTTPS)
    image: ${CI_REGISTRY:-registry.vecskill.ovec}/${IMAGE_NAME:-vecskill}-backend:${IMAGE_TAG:-latest}
    env_file:
      - ./deploy/.env
    environment:
      DB_HOST: db
    ports:
      - "7000:7000"
    volumes:
      - uploads_data:/app/uploads
    depends_on:
      db:
        condition: service_healthy
    restart: unless-stopped
    networks: [app_net]

  frontend:
    image: ${CI_REGISTRY:-registry.vecskill.ovec}/${IMAGE_NAME:-vecskill}-frontend:${IMAGE_TAG:-latest}
    environment:
      NITRO_PORT: 3000
      # Browser เรียก URL นี้ตรงๆ (กำหนดจาก Pipeline หรือ .env.production)
      NUXT_PUBLIC_API_BASE: ${NUXT_PUBLIC_API_BASE:-http://192.168.100.102:7000}
    ports:
      - "3000:3000"
    depends_on:
      - backend
    restart: unless-stopped
    networks: [app_net]

volumes:
  db_data:
  uploads_data:

networks:
  app_net:
    external: true</code></pre>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>ทำไม healthcheck ต้องมี <code>-h 127.0.0.1</code>:</strong> ตอนสร้างฐานข้อมูลครั้งแรก MySQL จะเปิด Server ชั่วคราว (ยังไม่เปิดพอร์ต 3306) เพื่อโหลด <code>schema.sql</code> ถ้า healthcheck ไม่ระบุ <code>-h</code> จะตรวจผ่าน Socket และขึ้น <code>healthy</code> ตั้งแต่ช่วงนั้น
  Backend จึงสตาร์ตก่อนฐานข้อมูลพร้อม และ Login ได้ <code>500 (ECONNREFUSED)</code> อยู่ช่วงหนึ่ง จากการทดสอบ เมื่อมี <code>-h 127.0.0.1</code> แล้ว Backend รอจนฐานข้อมูลพร้อมจริงจึงเริ่มทำงาน
</p>
<p><strong>การเตรียมไฟล์บน Production:</strong> Pipeline ส่ง <code>docker-compose.prod.yml</code> ไปที่ <code>/opt/vecskill/</code> และส่ง <code>schema.sql</code> กับ <code>deploy/.env.example</code> ไปที่ <code>/opt/vecskill/deploy/</code> ส่วน Source Code ไม่ถูกส่งไป ส่วน <code>phpmyadmin</code> อยู่ใน Profile <code>tools</code> จึงไม่รันเองตอน Deploy</p>

<h3>6.3 .gitlab-ci.yml</h3>
<p>โค้ดด้านล่างตรงกับไฟล์ในโปรเจกต์ โดยย่อคอมเมนต์</p>
<pre><code class="language-yaml">stages: [test, "build and push", deploy]

# ===== ตัวแปรกลาง =====
variables:
  DOCKER_DRIVER: overlay2
  # ปิด TLS ระหว่าง docker CLI กับ dind (ต้องตั้งคู่กับ DOCKER_HOST)
  DOCKER_TLS_CERTDIR: ""
  DOCKER_HOST: "tcp://docker:2375"
  # ชื่อ Image ต้องตรงกับ docker-compose.prod.yml
  IMAGE_NAME: "vecskill"
  IMAGE_TAG: "${CI_COMMIT_SHORT_SHA}"
  BACKEND_IMAGE: "${CI_REGISTRY}/${IMAGE_NAME}-backend:${IMAGE_TAG}"
  FRONTEND_IMAGE: "${CI_REGISTRY}/${IMAGE_NAME}-frontend:${IMAGE_TAG}"

  # เครื่อง Production (แก้ให้ตรงกับเครื่องจริง)
  DEPLOY_HOST: "192.168.100.102"
  DEPLOY_PORT: "22"
  DEPLOY_USER: "devops"
  DEPLOY_PATH: "/opt/vecskill"
  # "true" = Deploy ทันทีเมื่อ push เข้า main, "false" = ต้องกดปุ่ม Play
  AUTO_DEPLOY: "true"

default:
  tags: [docker]
  cache:
    key: ${CI_COMMIT_REF_SLUG}
    paths:
      - frontend/node_modules
      - backend/node_modules

# ===== TEST =====
test_backend:
  stage: test
  image: node:20-alpine
  services:
    - name: mysql:8.0
      command: ["--default-authentication-plugin=mysql_native_password"]
  variables:
    MYSQL_ROOT_PASSWORD: rootpassword
    MYSQL_DATABASE: skills_db
    DB_HOST: mysql
    DB_PORT: "3306"
    DB_USER: root
    DB_PASS: rootpassword
    DB_NAME: skills_db
  before_script:
    - apk add --no-cache mysql-client
    - |
      for i in $(seq 1 30); do
        mysqladmin ping -h mysql -u root -p"$MYSQL_ROOT_PASSWORD" --skip-ssl --silent &amp;&amp; break
        sleep 2
      done
    - mysql -h mysql -u root -p"$MYSQL_ROOT_PASSWORD" --skip-ssl &lt; schema.sql
  script:
    - cd backend
    - npm ci
    - npm run lint --if-present
    - npm test --if-present
  rules:
    - changes: ["backend/**"]
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"

test_frontend:
  stage: test
  image: node:20-alpine
  script:
    - cd frontend
    - npm ci
    - npm run lint --if-present
    - npm test --if-present
  rules:
    - changes: ["frontend/**"]
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"

# ===== BUILD AND PUSH (Docker-in-Docker) =====
.build_template:
  image: docker:26
  services:
    - name: docker:26-dind
      command: ["--insecure-registry=registry:5000"]

build_backend:
  stage: "build and push"
  extends: .build_template
  needs: ["test_backend"]
  script:
    - docker build -t "$BACKEND_IMAGE" ./backend
    - docker push "$BACKEND_IMAGE"
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then
        docker tag "$BACKEND_IMAGE" "${CI_REGISTRY}/${IMAGE_NAME}-backend:latest"
        docker push "${CI_REGISTRY}/${IMAGE_NAME}-backend:latest"
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["backend/**"]

build_frontend:
  stage: "build and push"
  extends: .build_template
  needs: ["test_frontend"]
  script:
    - docker build -t "$FRONTEND_IMAGE" ./frontend
    - docker push "$FRONTEND_IMAGE"
    - |
      if [ "$CI_COMMIT_BRANCH" = "main" ]; then
        docker tag "$FRONTEND_IMAGE" "${CI_REGISTRY}/${IMAGE_NAME}-frontend:latest"
        docker push "${CI_REGISTRY}/${IMAGE_NAME}-frontend:latest"
      fi
  rules:
    - if: $CI_COMMIT_BRANCH
      changes: ["frontend/**"]

# ===== DEPLOY =====
deploy_prod:
  stage: deploy
  image: alpine:3.20
  needs:
    - job: build_backend
      optional: true
    - job: build_frontend
      optional: true
  environment:
    name: production
    url: http://$DEPLOY_HOST:3000
    on_stop: stop_prod
  before_script:
    - apk add --no-cache openssh-client rsync bash
    - mkdir -p ~/.ssh &amp;&amp; chmod 700 ~/.ssh
    - echo "$DEPLOY_PRIV_KEY" &gt; ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" &gt;&gt; ~/.ssh/known_hosts || true
    - export REMOTE_DIR="${DEPLOY_PATH}"
  script:
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "sudo mkdir -p $REMOTE_DIR/deploy &amp;&amp; sudo chown -R \$USER:\$USER $REMOTE_DIR"
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" docker-compose.prod.yml "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/"
    - rsync -avz -e "ssh -p ${DEPLOY_PORT} -i ~/.ssh/id_ed25519" schema.sql deploy/.env.example "${DEPLOY_USER}@${DEPLOY_HOST}:$REMOTE_DIR/deploy/"
    # จงใจไม่ส่ง CI_REGISTRY: Production ใช้ค่าจาก .env.production หรือค่าเริ่มต้น (HTTPS)
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd $REMOTE_DIR &amp;&amp; IMAGE_NAME='${IMAGE_NAME}' IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000' docker compose -f docker-compose.prod.yml --env-file .env.production pull"
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd $REMOTE_DIR &amp;&amp; IMAGE_NAME='${IMAGE_NAME}' IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE='http://${DEPLOY_HOST}:7000' docker compose -f docker-compose.prod.yml --env-file .env.production up -d --remove-orphans"
  rules:
    - if: '$CI_COMMIT_BRANCH == "main" &amp;&amp; $AUTO_DEPLOY == "true"'
      when: on_success
      allow_failure: false
    - if: '$CI_COMMIT_BRANCH == "main"'
      when: manual
      allow_failure: false

# ===== STOP (กดเองเท่านั้น) =====
stop_prod:
  stage: deploy
  image: alpine:3.20
  environment:
    name: production
    action: stop
  before_script:
    - apk add --no-cache openssh-client
    - mkdir -p ~/.ssh &amp;&amp; chmod 700 ~/.ssh
    - echo "$DEPLOY_PRIV_KEY" &gt; ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - ssh-keyscan -p "${DEPLOY_PORT}" "$DEPLOY_HOST" &gt;&gt; ~/.ssh/known_hosts || true
  script:
    - ssh -p "$DEPLOY_PORT" -i ~/.ssh/id_ed25519 "${DEPLOY_USER}@${DEPLOY_HOST}" "cd ${DEPLOY_PATH} &amp;&amp; docker compose -f docker-compose.prod.yml down"
  rules:
    - when: manual
      allow_failure: true</code></pre>

<h3>6.4 อธิบายการทำงานทีละส่วน</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>ส่วนของโค้ด</th>
      <th>คำอธิบาย</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>default: tags: [docker]</code></td>
      <td>ทุก Job ใช้ Runner ที่มี Tag <code>docker</code> ถ้า Runner ไม่มี Tag นี้ Job จะค้าง Pending</td>
    </tr>
    <tr>
      <td><code>test_backend</code> : <code>services: mysql:8.0</code></td>
      <td>สร้าง MySQL ชั่วคราวคู่กับ Job เรียกด้วยชื่อ <code>mysql</code> แล้วโหลด <code>schema.sql</code> ก่อนรันเทสต์ ใช้ <code>mysql_native_password</code> และ <code>--skip-ssl</code> เพื่อให้ Client บน Alpine เชื่อมต่อได้ (ใช้เฉพาะฐานข้อมูลชั่วคราว)</td>
    </tr>
    <tr>
      <td><code>npm run lint --if-present</code></td>
      <td>ข้ามโดยไม่ Error ถ้าไม่มี Script นั้น (Backend มี <code>lint</code> แบบตัวแทน ส่วน Frontend ยังไม่มี)</td>
    </tr>
    <tr>
      <td><code>rules: changes:</code></td>
      <td>รัน Test/Build เฉพาะส่วนที่มีการแก้ไฟล์ (<code>backend/</code> หรือ <code>frontend/</code>) การแก้เฉพาะ <code>schema.sql</code> หรือไฟล์ Compose จะไม่รันเทสต์</td>
    </tr>
    <tr>
      <td><code>DOCKER_TLS_CERTDIR: ""</code><br /><code>DOCKER_HOST: tcp://docker:2375</code></td>
      <td>ให้ Docker CLI คุยกับ dind ภายใน Job โดยไม่ใช้ TLS ต้องตั้งคู่กันเสมอ</td>
    </tr>
    <tr>
      <td><code>--insecure-registry=registry:5000</code></td>
      <td>อนุญาตให้ dind Push ผ่าน HTTP ไปที่ Registry ภายใน dind ไม่อ่าน <code>daemon.json</code> ของเครื่อง Host จึงต้องส่งเป็น Flag</td>
    </tr>
    <tr>
      <td>ไม่มี <code>docker login</code></td>
      <td>Registry ตามคู่มือไม่มีระบบยืนยันตัวตน (ใช้ในเครือข่ายภายในที่เชื่อถือได้เท่านั้น)</td>
    </tr>
    <tr>
      <td><code>IMAGE_TAG</code> และ <code>:latest</code></td>
      <td>ทุก Branch ได้ Image ที่ Tag ด้วย Commit SHA ส่วน <code>latest</code> อัปเดตเฉพาะ Branch <code>main</code></td>
    </tr>
    <tr>
      <td><code>needs: optional: true</code></td>
      <td>Deploy ได้แม้รอบนั้นไม่มี Job Build ฝั่งใดฝั่งหนึ่ง (เพราะไม่ได้แก้ไฟล์ฝั่งนั้น)</td>
    </tr>
    <tr>
      <td><code>IMAGE_TAG='latest' NUXT_PUBLIC_API_BASE=...</code></td>
      <td>ตัวแปรที่ส่งผ่าน SSH ทับค่าใน <code>.env.production</code> และจงใจไม่ส่ง <code>CI_REGISTRY</code> ไปด้วย</td>
    </tr>
    <tr>
      <td><code>rules</code> ของ <code>deploy_prod</code></td>
      <td>เฉพาะ <code>main</code>: <code>AUTO_DEPLOY</code> เป็น <code>"true"</code> จะ Deploy ทันทีหลัง Build ผ่าน นอกนั้นต้องกด Play เอง</td>
    </tr>
    <tr>
      <td><code>stop_prod</code></td>
      <td>สั่ง <code>docker compose down</code> (ข้อมูลใน Volume ยังอยู่) ปุ่มนี้ปรากฏใน<strong>ทุก Pipeline ทุก Branch</strong> จึงต้องระวังการกดผิด</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 7: Push โค้ดและตรวจสอบผล</h2>

<h3>7.1 เช็กลิสต์ก่อน Push</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>รายการ</th>
      <th>วิธีตรวจ</th>
      <th>ผลที่ถูกต้อง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Runner พร้อม</td>
      <td>หน้าเว็บ GitLab &gt; Runners</td>
      <td>Online และมี Tag <code>docker</code></td>
    </tr>
    <tr>
      <td><code>config.toml</code></td>
      <td>ส่วนที่ 4.1</td>
      <td>ไม่มี <code>docker.sock</code>, มี <code>network_mode = "app_net"</code></td>
    </tr>
    <tr>
      <td>Production ดึง Image ได้</td>
      <td><code>curl https://registry.vecskill.ovec/v2/_catalog</code></td>
      <td>ได้ JSON</td>
    </tr>
    <tr>
      <td>สิทธิ์ของ <code>devops</code></td>
      <td><code>docker ps</code> และ <code>sudo -n true</code></td>
      <td>ไม่ถามรหัสผ่าน ไม่ติด Permission</td>
    </tr>
    <tr>
      <td>Network</td>
      <td><code>docker network ls</code> (Production)</td>
      <td>มี <code>app_net</code></td>
    </tr>
    <tr>
      <td>ไฟล์ค่าลับ</td>
      <td><code>ls /opt/vecskill/.env.production /opt/vecskill/deploy/.env</code></td>
      <td>มีครบ 2 ไฟล์</td>
    </tr>
    <tr>
      <td>GitLab Variables</td>
      <td>Settings &gt; CI/CD &gt; Variables</td>
      <td>มี <code>CI_REGISTRY</code> และ <code>DEPLOY_PRIV_KEY</code></td>
    </tr>
    <tr>
      <td>IP ในไฟล์ Pipeline</td>
      <td><code>DEPLOY_HOST</code></td>
      <td>ตรงกับเครื่อง Production จริง</td>
    </tr>
  </tbody>
</table>

<h3>7.2 Push ขึ้น GitLab</h3>
<p>ตรวจค่าตามส่วนที่ 1.2 แล้วรันในโฟลเดอร์ <code>pes-main</code> (รายละเอียดแต่ละคำสั่งดูในบท <a href="#" onclick="document.querySelector('a[data-page=\'basic-git-usage\']').click();">การใช้งาน Git เบื้องต้น</a>):</p>
<pre><code>git init --initial-branch=main
git remote add origin https://gitlab.vecskill.ovec/devops/pes.git
git status
git add .
git commit -m "Setup PES with CI/CD"
git push --set-upstream origin main</code></pre>
<p>ตอน <code>git status</code> ต้องไม่เห็นไฟล์ <code>.env</code> ตัวจริง ส่วนตอนถามรหัสผ่านให้ใช้ Token จากส่วนที่ 0</p>

<h3>7.3 ดูผล Pipeline</h3>
<ol>
  <li>ไปที่ Project <code>devops/pes</code> &gt; <strong>Build</strong> &gt; <strong>Pipelines</strong></li>
  <li>Push ครั้งแรกมีไฟล์ทั้งสองฝั่ง จึงควรเห็น <code>test_backend</code>, <code>test_frontend</code>, <code>build_backend</code>, <code>build_frontend</code>, <code>deploy_prod</code> ผ่านตามลำดับ (ถ้าตั้ง <code>AUTO_DEPLOY=false</code> ต้องกด Play ที่ <code>deploy_prod</code>)</li>
  <li>เปิด Registry UI ต้องเห็น <code>vecskill-backend</code> และ <code>vecskill-frontend</code> ที่มี Tag <code>latest</code> และ Tag ตาม Commit SHA</li>
</ol>
<p style="font-size: 0.9em; color: #666">ถ้าแก้เฉพาะ <code>.gitlab-ci.yml</code> หลังจากนั้น จะไม่มี Job Test/Build เพราะ <code>rules: changes</code> ไม่ตรงเงื่อนไข ไม่ใช่ Error</p>

<h3>7.4 ตรวจสอบบน Production</h3>
<pre><code class="language-bash">cd /opt/vecskill
docker compose -f docker-compose.prod.yml --env-file .env.production ps
curl http://localhost:7000/health</code></pre>
<table class="spec-table">
  <thead>
    <tr>
      <th>บริการ</th>
      <th>URL</th>
      <th>ผลที่ถูกต้อง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Frontend</td>
      <td><code>http://192.168.100.102:3000</code></td>
      <td>Login ด้วยบัญชีตัวอย่างได้ และแสดงข้อมูลในตาราง</td>
    </tr>
    <tr>
      <td>Backend API</td>
      <td><code>http://192.168.100.102:7000/docs</code></td>
      <td>เห็น Swagger UI</td>
    </tr>
    <tr>
      <td>phpMyAdmin (เปิดเมื่อต้องใช้)</td>
      <td><code>http://192.168.100.102:8080</code></td>
      <td>ใช้คำสั่งด้านล่างก่อน แล้ว Login ด้วย <code>root</code> / ค่า <code>MYSQL_ROOT_PASSWORD</code></td>
    </tr>
  </tbody>
</table>
<pre><code class="language-bash">cd /opt/vecskill
docker compose -f docker-compose.prod.yml --env-file .env.production --profile tools up -d phpmyadmin</code></pre>
<p>สุดท้ายยืนยันทั้งระบบด้วย E2E Test จากเครื่องนักพัฒนา (ตามส่วนที่ 3.4):</p>
<pre><code class="language-powershell">cd frontend
$env:E2E_BASE_URL="http://192.168.100.102:3000"; npm run test:e2e</code></pre>

<h3>7.5 ย้อนกลับไปเวอร์ชันก่อนหน้า (Rollback)</h3>
<p>ดู Commit SHA ของเวอร์ชันที่ต้องการจาก Registry UI แล้วทำบน Production (ตัวอย่างย้อน Backend ไปที่ <code>abc1234</code>):</p>
<pre><code class="language-bash">cd /opt/vecskill
docker pull registry.vecskill.ovec/vecskill-backend:abc1234
docker tag registry.vecskill.ovec/vecskill-backend:abc1234 registry.vecskill.ovec/vecskill-backend:latest
docker compose -f docker-compose.prod.yml --env-file .env.production up -d backend</code></pre>
<p style="font-size: 0.9em; color: #666">เป็นการย้อนชั่วคราวบนเครื่อง Production เท่านั้น การ Deploy รอบถัดไปจะดึง <code>latest</code> จาก Registry มาแทน ถ้าต้องการย้อนถาวรให้ Revert Commit แล้ว Push</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 8 (ส่วนเสริม): ให้ Pipeline รัน E2E Test หลัง Deploy</h2>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  Job นี้<strong>ไม่มีอยู่ในไฟล์ <code>.gitlab-ci.yml</code> ของโปรเจกต์</strong> เป็นตัวอย่างต่อยอดที่คู่มือเพิ่มให้ ตัวเทสต์ Playwright และสคริปต์รอ API ทดสอบแล้วบนเครื่องพัฒนา แต่<strong>ยังไม่ได้ทดลองรัน Job นี้บน GitLab Runner จริง</strong>
</p>
<p>แก้บรรทัด <code>stages</code> บนสุดของไฟล์ แล้วเพิ่ม Job ต่อท้าย:</p>
<pre><code class="language-yaml">stages: [test, "build and push", deploy, e2e]   # แก้บรรทัดเดิม: เพิ่ม e2e

e2e_prod:
  stage: e2e
  image: mcr.microsoft.com/playwright:v1.65.1-noble   # ต้องตรงกับเวอร์ชัน @playwright/test
  needs: ["deploy_prod"]
  variables:
    E2E_BASE_URL: "http://${DEPLOY_HOST}:3000"
  script:
    - cd frontend
    - npm ci
    # รอจนกว่า Login ผ่าน API ได้จริง (สูงสุดประมาณ 3 นาที)
    - |
      node -e '
      const url = "http://" + process.env.DEPLOY_HOST + ":7000/api/auth/login";
      const body = JSON.stringify({ email: "admin@vecskill.ovec", password: "12345678" });
      (async () =&gt; {
        for (let i = 0; i &lt; 60; i++) {
          try {
            const r = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body });
            if (r.ok) return console.log("API ready");
          } catch {}
          await new Promise((s) =&gt; setTimeout(s, 3000));
        }
        process.exit(1);
      })();'
    - npm run test:e2e -- --retries=2
  artifacts:
    when: on_failure
    paths: [frontend/test-results/]
    expire_in: 1 week
  rules:
    - if: $CI_COMMIT_BRANCH == "main"</code></pre>
<ul>
  <li>เวอร์ชันของ Image <code>mcr.microsoft.com/playwright</code> ต้องตรงกับ <code>@playwright/test</code> ใน <code>frontend/package-lock.json</code> (ในโปรเจกต์คือ <code>1.65.1</code>)</li>
  <li>Image นี้มีขนาดใหญ่ Runner ต้องดึงจากอินเทอร์เน็ตได้ หรือดึงมาเก็บใน Registry ภายในก่อน แล้วเปลี่ยนชื่อ <code>image:</code></li>
  <li>เมื่อไม่ผ่าน ดาวน์โหลด Artifacts จากหน้า Job เพื่อเปิดดูภาพหน้าจอและ Trace ได้</li>
  <li>เทสต์รันหลัง Deploy แล้ว ถ้าไม่ผ่าน Pipeline จะแดง แต่ระบบบน Production ไม่ถูกย้อนกลับให้เอง และเทสต์จะเขียนข้อมูลลง Production จริง ระบบใช้งานจริงควรมีเครื่อง Staging แยก</li>
</ul>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 9: ปัญหาที่อาจพบ วิธีตรวจสอบ และวิธีแก้</h2>
<p>
  หาให้เจอก่อนว่าพังที่ขั้นไหน (Runner, Test, Build/Push, Deploy หรือหลัง Deploy) แล้วเปิดหัวข้อนั้น ข้อความ Error ของ Pipeline ดูได้จากหน้า Log ของ Job (<strong>Build</strong> &gt; <strong>Pipelines</strong> &gt; คลิกชื่อ Job)
</p>

<h3>9.1 คำสั่งตรวจสอบพื้นฐาน</h3>
<p><strong>บน Server 1</strong> (Runner และ Registry):</p>
<pre><code class="language-bash">docker ps --format 'table {{.Names}}\t{{.Status}}'
docker logs --tail 50 gitlab-runner
docker exec gitlab-runner gitlab-runner verify
docker network inspect app_net --format '{{range .Containers}}{{.Name}} {{end}}'
curl http://localhost:5000/v2/_catalog</code></pre>
<p><strong>บน Production</strong> (ตัวแปร <code>DC</code> ช่วยไม่ต้องพิมพ์ยาว ใช้ต่อในทุกคำสั่งของส่วนนี้):</p>
<pre><code class="language-bash">cd /opt/vecskill
DC="docker compose -f docker-compose.prod.yml --env-file .env.production"
$DC ps -a
$DC logs --tail 50 backend
$DC logs --tail 50 db
$DC logs --tail 50 frontend
$DC images</code></pre>
<p style="font-size: 0.9em; color: #666">ถ้าสั่ง <code>docker compose</code> โดยไม่ใส่ <code>-f docker-compose.prod.yml</code> จะขึ้น <code>no configuration file provided</code> ส่วน <code>$DC config</code> ใช้ดูค่าที่ถูกแทนจริง แต่จะแสดงรหัสผ่านด้วย ห้ามแชร์ผลลัพธ์</p>

<h3>9.2 Runner และการสร้าง Pipeline</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุและวิธีตรวจ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Push แล้วไม่มี Pipeline หรือขึ้น <code>yaml invalid</code></td>
      <td>ไฟล์ <code>.gitlab-ci.yml</code> ผิดรูปแบบ ตรวจที่ <strong>Build</strong> &gt; <strong>Pipeline editor</strong></td>
      <td>แก้ตามข้อความของ Validator (มักเป็นการเยื้องบรรทัด)</td>
    </tr>
    <tr>
      <td>Job ค้าง <code>Pending</code> / <code>This job is stuck</code></td>
      <td>Runner Offline หรือไม่มี Tag <code>docker</code> ดูหน้า Runners และ Log ของ Runner</td>
      <td>เพิ่ม Tag หรือ <code>docker restart gitlab-runner</code></td>
    </tr>
    <tr>
      <td><code>could not resolve host: gitlab</code> หรือ <code>dial tcp ...:443: connection refused</code> ตอน Clone</td>
      <td>ค่าใน <code>config.toml</code></td>
      <td><code>url</code>/<code>clone_url = "http://gitlab"</code> และ <code>network_mode = "app_net"</code></td>
    </tr>
    <tr>
      <td>Job ที่คาดไว้ไม่ปรากฏ</td>
      <td><code>rules: changes</code> ไม่ตรง (ไม่ได้แก้ไฟล์ใน <code>backend/</code> หรือ <code>frontend/</code>) ไม่ใช่ Error</td>
      <td>Commit ที่แก้ไฟล์ในโฟลเดอร์นั้น</td>
    </tr>
    <tr>
      <td>ตัวแปรว่างเปล่าใน Job</td>
      <td>ติ๊ก <strong>Protect variable</strong> แต่ Branch ไม่ Protected ตรวจโดยเพิ่ม <code>echo "CI_REGISTRY=[$CI_REGISTRY]"</code> ชั่วคราว (ห้าม echo ค่าลับ)</td>
      <td>เอา Protect ออก หรือตั้ง Branch เป็น Protected</td>
    </tr>
    <tr>
      <td>ดึง Image ของ Job ไม่ได้ / <code>toomanyrequests</code></td>
      <td>Runner ออกอินเทอร์เน็ตไม่ได้ หรือชน Rate Limit ของ Docker Hub</td>
      <td>ดึง Image ไว้ล่วงหน้า, ใช้ <code>pull_policy = ["if-not-present"]</code> หรือใช้ Mirror</td>
    </tr>
  </tbody>
</table>

<h3>9.3 Stage test</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุและวิธีตรวจ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>ECONNREFUSED 127.0.0.1:3306</code></td>
      <td>Job ไม่มี MySQL หรือไม่ได้ชี้ไปที่ Service</td>
      <td>มี <code>services: mysql:8.0</code> และ <code>DB_HOST: mysql</code></td>
    </tr>
    <tr>
      <td><code>ERROR 2026 ... self-signed certificate</code></td>
      <td>Client บน Alpine บังคับ TLS</td>
      <td>ใส่ <code>--skip-ssl</code> ที่ <code>mysqladmin</code> และ <code>mysql</code></td>
    </tr>
    <tr>
      <td><code>ERROR 1045 ... caching_sha2_password could not be loaded</code></td>
      <td>Client บน Alpine ไม่มี Plugin ของ MySQL 8</td>
      <td><code>command: ["--default-authentication-plugin=mysql_native_password"]</code></td>
    </tr>
    <tr>
      <td><code>unknown variable 'default-authentication-plugin=...'</code></td>
      <td>เปลี่ยน Service เป็น <code>mysql:8.4</code> ซึ่งไม่รู้จัก Option นี้แล้ว</td>
      <td>ใช้ <code>mysql:8.0</code> ตามไฟล์ในโปรเจกต์</td>
    </tr>
    <tr>
      <td><code>ERROR 2002 ... Can't connect ... 'mysql'</code> ตอนโหลด <code>schema.sql</code></td>
      <td>MySQL ยังไม่พร้อมภายในเวลาที่วนรอ (ประมาณ 60 วินาที แล้วทำต่อโดยไม่แจ้ง)</td>
      <td>เพิ่มรอบ <code>seq 1 30</code> เป็น <code>seq 1 60</code> หรือกด Retry</td>
    </tr>
    <tr>
      <td><code>WARNING: Skipping alias "mysql"</code></td>
      <td>ตั้ง <code>alias: mysql</code> ซ้ำกับชื่อที่ GitLab ตั้งให้เอง</td>
      <td>ไม่กระทบผล ลบ <code>alias</code> ได้</td>
    </tr>
    <tr>
      <td><code>npm ci</code> แจ้งว่า <code>package.json</code> กับ <code>package-lock.json</code> ไม่ตรงกัน</td>
      <td>เพิ่ม Package แล้วไม่ได้ Commit ไฟล์ Lock</td>
      <td>รัน <code>npm install</code> บนเครื่องตัวเอง แล้ว Commit ทั้งสองไฟล์</td>
    </tr>
    <tr>
      <td><code>apk add</code> ล้มเหลว / <code>npm ci</code> ค้างหรือ Timeout</td>
      <td>เข้า Alpine Repository หรือ NPM Registry ไม่ได้</td>
      <td>ตรวจอินเทอร์เน็ต หรือชี้ไปที่ Mirror ภายใน</td>
    </tr>
    <tr>
      <td>รันบนเครื่องตัวเองแล้วต่อฐานข้อมูลไม่ได้</td>
      <td><code>db</code> ยังไม่รัน หรือพอร์ต <code>3306</code> ไปชน MySQL ของ XAMPP</td>
      <td>สั่งเทสต์ใน Container ด้วย <code>docker compose exec backend npm test</code></td>
    </tr>
  </tbody>
</table>

<h3>9.4 Stage build and push</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุและวิธีตรวจ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>Cannot connect to the Docker daemon at tcp://docker:2375</code></td>
      <td>dind ไม่ขึ้น หรือฟังคนละพอร์ต ดู Log ช่วงต้นของ Job</td>
      <td><code>privileged = true</code>, <code>DOCKER_TLS_CERTDIR: ""</code>, <code>DOCKER_HOST: tcp://docker:2375</code></td>
    </tr>
    <tr>
      <td><code>Client sent an HTTP request to an HTTPS server</code></td>
      <td>ตั้งค่า TLS ของ dind ไม่ครบคู่</td>
      <td>ใช้ค่าตามแถวบน (พอร์ต 2375 ไม่ใช้ TLS)</td>
    </tr>
    <tr>
      <td><code>device or resource busy</code></td>
      <td><code>config.toml</code> mount <code>docker.sock</code> อยู่</td>
      <td><code>volumes = ["/cache"]</code> แล้ว Restart Runner</td>
    </tr>
    <tr>
      <td><code>invalid reference format</code></td>
      <td><code>CI_REGISTRY</code> ว่าง ชื่อ Image จึงขึ้นต้นด้วย <code>/</code></td>
      <td>เพิ่มตัวแปร <code>CI_REGISTRY = registry:5000</code></td>
    </tr>
    <tr>
      <td><code>lookup registry.vecskill.ovec on 127.0.0.11:53: no such host</code></td>
      <td>DNS ภายใน Docker ไม่รู้จักชื่อโดเมนเต็ม</td>
      <td>ใช้ <code>registry:5000</code></td>
    </tr>
    <tr>
      <td><code>lookup registry ... no such host</code> หรือ <code>connection refused</code></td>
      <td>Container <code>registry</code> หยุด, ไม่อยู่ใน <code>app_net</code> หรืออยู่คนละเครื่อง (ตรวจตามข้อ 9.1)</td>
      <td>เปิด Container หรือใช้ <code>IP:5000</code> และเปิด Firewall พอร์ต 5000</td>
    </tr>
    <tr>
      <td><code>server gave HTTP response to HTTPS client</code></td>
      <td><code>--insecure-registry</code> ไม่ตรงกับ <code>CI_REGISTRY</code> หรือมี <code>docker login</code> (เกิดเมื่อเปิด Container Registry ของ GitLab ไว้)</td>
      <td>ให้สองค่าตรงกันทุกตัวอักษร ไม่ใส่ <code>docker login</code></td>
    </tr>
    <tr>
      <td><code>npm ci</code> ล้มเหลวระหว่าง <code>docker build</code></td>
      <td>Container ที่กำลัง Build เข้า NPM Registry ไม่ได้</td>
      <td>ตรวจอินเทอร์เน็ต หรือกำหนด Registry ภายในใน Dockerfile</td>
    </tr>
    <tr>
      <td><code>no space left on device</code></td>
      <td>ดิสก์ของ Server 1 เต็ม ตรวจด้วย <code>df -h</code> และ <code>docker system df</code></td>
      <td>ลบ Image/Cache ที่ไม่ใช้ (ตรวจรายการก่อนลบ)</td>
    </tr>
  </tbody>
</table>

<h3>9.5 Stage deploy</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุและวิธีตรวจ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>ssh: connect ... timed out</code> / <code>Connection refused</code></td>
      <td>จาก Server 1 รัน <code>nc -zv 192.168.100.102 22</code></td>
      <td>ตรวจ Routing, Firewall, บริการ SSH และ <code>DEPLOY_HOST</code></td>
    </tr>
    <tr>
      <td><code>Host key verification failed</code></td>
      <td><code>ssh-keyscan</code> ในสคริปต์ล้มเหลวแบบเงียบ (มี <code>|| true</code>)</td>
      <td>ตรวจการเชื่อมต่อและ <code>DEPLOY_PORT</code></td>
    </tr>
    <tr>
      <td><code>Load key ... error in libcrypto</code></td>
      <td>ค่า <code>DEPLOY_PRIV_KEY</code> ไม่ครบหรือรูปแบบเสีย</td>
      <td>คัดลอก Private Key ใหม่ทั้งหมด รวมบรรทัด BEGIN/END</td>
    </tr>
    <tr>
      <td><code>Permission denied (publickey,password)</code></td>
      <td>บน Production ดู <code>sudo journalctl -u ssh -n 30</code></td>
      <td>เพิ่ม Public Key ใน <code>authorized_keys</code> ของ <code>devops</code> สิทธิ์โฟลเดอร์ 700 ไฟล์ 600</td>
    </tr>
    <tr>
      <td><code>sudo: a terminal is required</code></td>
      <td>ยังไม่ได้ตั้ง NOPASSWD sudo</td>
      <td>ส่วนที่ 4.2</td>
    </tr>
    <tr>
      <td><code>rsync: command not found</code></td>
      <td>Production ไม่มี <code>rsync</code></td>
      <td><code>sudo apt install -y rsync</code></td>
    </tr>
    <tr>
      <td><code>env file ... not found</code></td>
      <td>ไม่มี <code>.env.production</code> หรือ <code>deploy/.env</code></td>
      <td>ส่วนที่ 4.5</td>
    </tr>
    <tr>
      <td><code>permission denied ... docker.sock</code></td>
      <td><code>devops</code> ไม่อยู่ในกลุ่ม <code>docker</code></td>
      <td><code>sudo usermod -aG docker devops</code> แล้ว Login ใหม่</td>
    </tr>
    <tr>
      <td><code>network app_net declared as external, but could not be found</code></td>
      <td>ยังไม่ได้สร้าง Network</td>
      <td><code>docker network create app_net</code></td>
    </tr>
    <tr>
      <td><code>lookup registry.vecskill.ovec ... no such host</code></td>
      <td><code>getent hosts registry.vecskill.ovec</code> ไม่มีผล (ค่าใน <code>/etc/hosts</code> อาจหายหลังติดตั้งเครื่องใหม่)</td>
      <td>ส่วนที่ 4.3 หรือตั้ง DNS</td>
    </tr>
    <tr>
      <td><code>x509: certificate signed by unknown authority</code></td>
      <td>ยังไม่เชื่อถือ Root CA ของ Caddy</td>
      <td>ติดตั้ง CA แล้ว <code>sudo systemctl restart docker</code></td>
    </tr>
    <tr>
      <td><code>vecskill-...:latest: not found</code></td>
      <td>Image ฝั่งนั้นยังไม่เคย Build บน <code>main</code> ตรวจใน Registry UI</td>
      <td>แก้ไฟล์ในโฟลเดอร์นั้นแล้ว Push</td>
    </tr>
    <tr>
      <td><code>port is already allocated</code></td>
      <td><code>sudo ss -ltnp | grep -E ':(3000|7000|8080)'</code></td>
      <td>หยุดบริการที่ใช้พอร์ตซ้ำ</td>
    </tr>
    <tr>
      <td><code>Database is uninitialized and password option is not specified</code></td>
      <td><code>MYSQL_ROOT_PASSWORD</code> ว่าง</td>
      <td>แก้ <code>.env.production</code></td>
    </tr>
    <tr>
      <td><code>container ...db-1 is unhealthy</code></td>
      <td>MySQL ยังไม่รับการเชื่อมต่อ ดู <code>$DC logs db</code> (กำลังโหลดข้อมูล, ดิสก์เต็ม หรือ SQL ผิด)</td>
      <td>แก้ตาม Log ถ้าเครื่องช้าให้เพิ่ม <code>start_period</code></td>
    </tr>
    <tr>
      <td>Pipeline เขียว แต่ระบบยังเป็นเวอร์ชันเก่า</td>
      <td>Push ที่ Branch อื่น หรือรอบนั้นไม่มี Job Build ตรวจด้วย <code>$DC images</code> เทียบกับ Registry UI</td>
      <td>Merge เข้า <code>main</code> และให้มี Job Build</td>
    </tr>
    <tr>
      <td>Deploy ทันทีทั้งที่ยังไม่พร้อม</td>
      <td><code>AUTO_DEPLOY</code> เป็น <code>"true"</code></td>
      <td>ตั้งตัวแปร <code>AUTO_DEPLOY = false</code></td>
    </tr>
  </tbody>
</table>

<h3>9.6 หลัง Deploy แล้วขึ้น <code>Network Error</code></h3>
<p>
  <code>Network Error</code> แปลว่า Browser ติดต่อ API ไม่สำเร็จ จากการทดสอบ หน้า Login แสดงข้อความนี้เหมือนกันทั้งกรณี CORS ผิด, Backend หยุด และ URL ของ API ผิด จึงต้องแยกสาเหตุจาก DevTools
</p>
<p><strong>ขั้นที่ 1:</strong> เปิดหน้าเว็บ กด <strong>F12</strong> ดูแท็บ <strong>Console</strong> และ <strong>Network</strong> แล้วลอง Login อีกครั้ง ที่อยู่ของ Request ต้องเป็น <code>http://192.168.100.102:7000/api/auth/login</code></p>
<table class="spec-table">
  <thead>
    <tr>
      <th>ข้อความใน Console</th>
      <th>สาเหตุ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>blocked by CORS policy ... No 'Access-Control-Allow-Origin' header</code> (Network ขึ้น <code>net::ERR_FAILED</code>)</td>
      <td><code>CORS_ORIGIN</code> ไม่ตรงกับ URL บน Address bar</td>
      <td>แก้ <code>deploy/.env</code> แล้ว <code>$DC up -d backend</code></td>
    </tr>
    <tr>
      <td><code>net::ERR_CONNECTION_REFUSED</code></td>
      <td>Backend หยุดหรือกำลังเริ่มใหม่ หรือเรียกผิดพอร์ต</td>
      <td><code>$DC ps -a</code> และ <code>$DC logs backend</code></td>
    </tr>
    <tr>
      <td><code>net::ERR_NAME_NOT_RESOLVED</code></td>
      <td><code>NUXT_PUBLIC_API_BASE</code> ใช้ชื่อที่เครื่องผู้ใช้ไม่รู้จัก</td>
      <td>แก้เป็น URL ที่ถูกต้อง แล้ว <code>$DC up -d frontend</code></td>
    </tr>
    <tr>
      <td><code>net::ERR_CONNECTION_TIMED_OUT</code></td>
      <td>IP ผิด, Firewall หรือ Routing ระหว่างวง Network</td>
      <td>ทดสอบตามขั้นที่ 2 และเปิดพอร์ต 3000, 7000</td>
    </tr>
    <tr>
      <td><code>Mixed Content</code></td>
      <td>เปิดหน้าเว็บด้วย HTTPS แต่ API เป็น HTTP</td>
      <td>ให้ API อยู่หลัง Reverse Proxy แบบ HTTPS แล้วแก้ <code>NUXT_PUBLIC_API_BASE</code> และ <code>CORS_ORIGIN</code></td>
    </tr>
    <tr>
      <td>หน้าจอขึ้น <code>Invalid response</code> (ไม่ใช่ Network Error)</td>
      <td>Request ได้ <code>200</code> แต่ไม่ใช่ API จริง เช่น URL ชี้ไปที่ Frontend</td>
      <td>ตรวจพอร์ตใน <code>NUXT_PUBLIC_API_BASE</code> ต้องเป็น <code>7000</code></td>
    </tr>
  </tbody>
</table>
<p><strong>ขั้นที่ 2:</strong> ตรวจการเชื่อมต่อจากเครื่องผู้ใช้ (Windows PowerShell):</p>
<pre><code class="language-powershell">Test-NetConnection 192.168.100.102 -Port 7000
curl.exe http://192.168.100.102:7000/health</code></pre>
<p><strong>ขั้นที่ 3:</strong> ชุดตรวจหลัง Deploy บน Production (คัดลอกรันได้ทั้งชุด):</p>
<pre><code class="language-bash">cd /opt/vecskill
DC="docker compose -f docker-compose.prod.yml --env-file .env.production"
$DC ps
curl -s http://localhost:7000/health; echo
curl -s -D - -o /dev/null -X OPTIONS \
  -H "Origin: http://192.168.100.102:3000" \
  -H "Access-Control-Request-Method: POST" \
  http://localhost:7000/api/auth/login | grep -iE "^HTTP|access-control-allow-origin"
curl -s -o /dev/null -w "login: %{http_code}\n" -H "Content-Type: application/json" \
  -d '{"email":"admin@vecskill.ovec","password":"12345678"}' http://localhost:7000/api/auth/login
$DC exec backend printenv CORS_ORIGIN
$DC exec frontend printenv NUXT_PUBLIC_API_BASE</code></pre>
<table class="spec-table">
  <thead>
    <tr>
      <th>รายการ</th>
      <th>ผลที่ถูกต้อง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>ps</code></td>
      <td><code>db</code> เป็น <code>healthy</code>, <code>backend</code> และ <code>frontend</code> เป็น <code>Up</code></td>
    </tr>
    <tr>
      <td><code>/health</code></td>
      <td>ได้ JSON</td>
    </tr>
    <tr>
      <td>Preflight (CORS)</td>
      <td>มีทั้ง <code>204</code> และบรรทัด <code>Access-Control-Allow-Origin: http://192.168.100.102:3000</code> — ถ้า CORS ผิดจะ<strong>ยังได้ 204 แต่ไม่มีบรรทัดนี้</strong></td>
    </tr>
    <tr>
      <td>Login</td>
      <td><code>200</code> — <code>curl</code> ได้ 200 แม้ CORS ผิด จึงต้องดูบรรทัด Preflight ด้วย</td>
    </tr>
    <tr>
      <td><code>printenv</code></td>
      <td>ค่าตรงกับ URL ที่ใช้จริง</td>
    </tr>
  </tbody>
</table>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>แก้ไฟล์ env แล้วต้องใช้ <code>up -d</code> ไม่ใช่ <code>restart</code>:</strong> <code>$DC restart backend</code> ยังใช้ค่าเดิม ส่วน <code>$DC up -d backend</code> จะสร้าง Container ใหม่ด้วยค่าใหม่
  (การ Deploy ผ่าน Pipeline กำหนด <code>NUXT_PUBLIC_API_BASE</code> จาก <code>DEPLOY_HOST</code> ทับค่าในไฟล์เสมอ)
</p>

<h3>9.7 อาการอื่นหลัง Deploy</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุและวิธีตรวจ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>403 Blocked request. This host (...) is not allowed.</code></td>
      <td>เปิดเว็บด้วยชื่อโดเมนที่ Dev server ไม่อนุญาต</td>
      <td>โปรเจกต์อนุญาต <code>.vecskill.ovec</code> ไว้ โดเมนอื่นให้เพิ่มใน <code>allowedHosts</code> (<code>frontend/nuxt.config.ts</code>) แล้ว Build ใหม่ และเพิ่ม URL ใน <code>CORS_ORIGIN</code></td>
    </tr>
    <tr>
      <td>หน้า Login ขึ้น <code>Access denied for user 'user1'@...</code> (HTTP 500)</td>
      <td><code>DB_PASS</code> ไม่ตรงกับรหัสผ่านใน MySQL</td>
      <td>ให้ตรงกับ <code>MYSQL_PASSWORD</code> แล้ว <code>$DC up -d backend</code></td>
    </tr>
    <tr>
      <td>แก้รหัสผ่านทั้งสองไฟล์แล้วยังได้ <code>500</code></td>
      <td>รหัสผ่านถูกสร้างตอน Volume <code>db_data</code> ว่างครั้งแรกเท่านั้น</td>
      <td>เปลี่ยนรหัสผ่านใน MySQL เอง (คำสั่งด้านล่าง)</td>
    </tr>
    <tr>
      <td>Login ได้ <code>500</code> ช่วงสั้นๆ หลัง Deploy ครั้งแรก และ Log ขึ้น <code>ECONNREFUSED ...:3306</code></td>
      <td>healthcheck ของ <code>db</code> ผ่านก่อนฐานข้อมูลพร้อม</td>
      <td>ต้องมี <code>-h 127.0.0.1</code> ใน healthcheck (ส่วนที่ 6.2)</td>
    </tr>
    <tr>
      <td>บัญชีตัวอย่าง Login ไม่ได้ หรือไม่มีตาราง</td>
      <td><code>schema.sql</code> ไม่ได้ถูกโหลด (Volume มีข้อมูลเดิมอยู่แล้ว)</td>
      <td>นำเข้า SQL เอง หรือเริ่มฐานข้อมูลใหม่ (ข้อ 9.9)</td>
    </tr>
    <tr>
      <td>แก้ <code>schema.sql</code> แล้วฐานข้อมูลไม่เปลี่ยน</td>
      <td>สคริปต์ใน <code>initdb.d</code> ทำงานครั้งแรกครั้งเดียว</td>
      <td>เขียนคำสั่ง <code>ALTER</code> รันเอง หรือข้อ 9.9</td>
    </tr>
    <tr>
      <td>หน้าเว็บขาวหรือโหลดช้าหลัง Deploy</td>
      <td>Dev server กำลังคอมไพล์ ดู <code>$DC logs frontend</code></td>
      <td>รอแล้ว Refresh</td>
    </tr>
  </tbody>
</table>
<pre><code class="language-bash"># เปิด MySQL บน Production (ใช้รหัส MYSQL_ROOT_PASSWORD เดิม)
$DC exec db mysql -uroot -p</code></pre>
<pre><code class="language-sql">ALTER USER 'user1'@'%' IDENTIFIED BY '__CHANGE_ME_USER__';</code></pre>

<h3>9.8 E2E Test</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>อาการ / ข้อความ Error</th>
      <th>สาเหตุ</th>
      <th>วิธีแก้</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>Executable doesn't exist</code></td>
      <td>ยังไม่ได้ติดตั้ง Browser หรือ Image ใน CI คนละเวอร์ชันกับ Package</td>
      <td><code>npx playwright install chromium</code> หรือปรับ Tag ของ Image</td>
    </tr>
    <tr>
      <td><code>net::ERR_CONNECTION_REFUSED</code> ตอน <code>page.goto</code></td>
      <td>ระบบไม่ได้รัน หรือ <code>E2E_BASE_URL</code> ผิด</td>
      <td>เปิดระบบก่อน แล้วตรวจ URL</td>
    </tr>
    <tr>
      <td><code>Test timeout of 30000ms exceeded</code></td>
      <td>หน้าแรกคอมไพล์ช้า</td>
      <td><code>npm run test:e2e -- --retries=1</code></td>
    </tr>
    <tr>
      <td>E2E-001 ไม่ผ่าน แต่ใช้เว็บเองได้</td>
      <td>รหัสผ่านบัญชีตัวอย่างถูกเปลี่ยน หรือข้อมูลต่างจาก <code>schema.sql</code></td>
      <td>ใช้ฐานข้อมูลสำหรับทดสอบ</td>
    </tr>
    <tr>
      <td>ไฟล์ทดสอบค้างใน <code>uploads</code></td>
      <td>เทสต์สั่งลบรายการแล้ว แต่ไฟล์จริงไม่ถูกลบ (พฤติกรรมของโค้ด Backend ปัจจุบัน)</td>
      <td>ลบเองเป็นระยะ หรือแก้โค้ดการต่อ Path ของไฟล์</td>
    </tr>
  </tbody>
</table>

<h3>9.9 หยุดระบบ และเริ่มฐานข้อมูลใหม่</h3>
<pre><code class="language-bash"># หยุดระบบ ข้อมูลใน Volume ยังอยู่
$DC down</code></pre>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>คำสั่งด้านล่างลบฐานข้อมูลและไฟล์อัปโหลดทั้งหมดบน Production กู้คืนไม่ได้</strong> ใช้เฉพาะระบบฝึกซ้อมที่ต้องการเริ่มใหม่ หรือสำรองข้อมูลแล้วเท่านั้น
</p>
<pre><code class="language-bash">$DC down -v
$DC up -d</code></pre>

<h3>9.10 หลักคิดเวลาแก้ปัญหา</h3>
<ul>
  <li>คนละเครื่องต้องรู้จักกันผ่าน DNS หรือ <code>/etc/hosts</code> ส่วนชื่อสั้นอย่าง <code>registry</code> ใช้ได้เฉพาะใน Docker network เดียวกันบนเครื่องเดียวกัน</li>
  <li>Job ที่ "หายไป" จาก <code>rules</code> ต่างจาก Job ที่ "ล้มเหลว"</li>
  <li>เมื่อแก้ปัญหาจุดหนึ่งแล้ว ให้ค้นว่ามีโค้ดแบบเดียวกันอยู่ใน Job อื่นด้วยหรือไม่</li>
  <li>ทดสอบด้วยมือบนเครื่องปลายทางให้ผ่านก่อน (เช่น <code>docker pull</code>, <code>ssh</code>) แล้วค่อยให้ Pipeline ทำ</li>
</ul>
