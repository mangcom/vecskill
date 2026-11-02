# IT VECSkill Guide: คู่มือเตรียมความพร้อมการแข่งขันทักษะ DevOps

"IT VECSkill Guide" คือโปรเจกต์คู่มือออนไลน์ (Single Page Application ฝั่ง Client เชื่อมกับ Backend PHP + MySQL) โดยมีจุดประสงค์หลักเพื่อใช้เป็น **คู่มือเตรียมความพร้อมสำหรับการแข่งขันทักษะวิชาชีพ** (ทักษะเทคโนโลยีสารสนเทศด้านโปรแกรมและเครือข่ายด้วยกระบวนการ DevOps)

คู่มือนี้ถูกออกแบบมาเพื่อ 2 กลุ่มเป้าหมายหลัก:

1.  **สำหรับผู้เข้าแข่งขัน (ทีม):**
    * ใช้เป็น **"คู่มือฝึกซ้อม" (Training Guide)** ที่สมบูรณ์แบบ
    * ครอบคลุมตั้งแต่การเตรียม Server, Docker/Portainer, Reverse Proxy + HTTPS (Caddy/NPM), GitLab + CI/CD, Registry, Security & Code Quality (OWASP ZAP, Trivy, SonarQube), API Docs (Swagger) ไปจนถึง DNS ภายใน (Technitium)

2.  **สำหรับเจ้าภาพ (ผู้จัดการแข่งขัน):**
    * ใช้เป็น **"คู่มืออ้างอิง" (Host's Playbook)**
    * ช่วยให้คณะกรรมการสามารถเตรียมสภาพแวดล้อม Server กลางสำหรับการแข่งขัน (เช่น Container Registry Server, Git Server, หรือ Package Mirror สำหรับเครือข่ายที่ไม่มีอินเทอร์เน็ต) ได้อย่างถูกต้องและเป็นมาตรฐานเดียวกัน

<div align="center">
  <img src="https://vecskill.bncc.ac.th/images/00-visio.png" alt="Project Architecture Diagram" width="700">
</div>

---

## 🚀 คุณสมบัติของหน้าเว็บ (Features)

* **Responsive Design:** รองรับการแสดงผลบนทุกอุปกรณ์ (Desktop/Mobile)
* **Dynamic Content Loading + Clean URL:** โหลดเนื้อหาแบบไดนามิกโดยใช้ JavaScript `fetch()` (ไม่โหลดหน้าใหม่) พร้อม URL จริงของแต่ละหน้า (เช่น `/docker`, `/gitlab`) ที่ Refresh/Bookmark/กด Back-Forward ได้ตามปกติ
* **Collapsible Sidebar:** เมนูที่สามารถพับเก็บ/ขยายได้ (ทั้งแบบอัตโนมัติบนมือถือ และแบบ Manual บน Desktop)
* **Copy Code Button:** ปุ่มคัดลอกโค้ดอัตโนมัติในทุกโค้ดบล็อก (`<pre>`)
* **Offline-First:** Asset หลัก (Font Awesome, jQuery, DataTables) และไฟล์ประกอบ (เช่น ภาษาไทยของ DataTables) ถูกเก็บไว้ในเครื่องทั้งหมด ไม่พึ่ง CDN ภายนอก เพื่อให้ใช้งานได้ในเครือข่ายปิดของสนามแข่งขัน
* **View Counter:** ระบบนับจำนวนการเข้าชมหน้าแต่ละหน้า (เก็บลงฐานข้อมูล MySQL ผ่าน `api/track.php`, `api/stats.php`)
* **Embedded Content:** รองรับการฝังเนื้อหาจากภายนอก เช่น Google Drive `<iframe>`

## 💻 เทคโนโลยีที่ใช้

* **Frontend:** HTML5, CSS3 (Flexbox, Grid, Media Queries), Vanilla JavaScript (ES6+, Fetch API, History API), Font Awesome
* **Backend:** PHP 8.2 + Apache (mod_rewrite สำหรับ Clean URL), MySQL 8.0
* **Deployment:** Docker + Docker Compose (ดูหัวข้อติดตั้งด้านล่าง) หรือ XAMPP

---

## 🛠️ การนำไปใช้งาน (Installation & Setup)

### 1. ดาวน์โหลดโปรเจกต์ (Git Clone)

คุณสามารถดาวน์โหลดโปรเจกต์นี้ไปใช้งานได้โดยใช้คำสั่ง `git clone`:

```
git clone https://github.com/mangcom/vecskill.git
```
จากนั้นเข้าไปยังโฟลเดอร์โปรเจกต์:
```
cd vecskill
```
### 2. วิธีการรันโปรเจกต์
โปรเจกต์นี้เป็น PHP (index.php, pages/*.php) ที่ต่อฐานข้อมูล MySQL จริง (api/stats.php, api/track.php) และใช้ Clean URL ผ่าน .htaccess
ดังนั้น**ต้องรันผ่าน Web Server ที่รองรับ PHP + mod_rewrite เท่านั้น** (VS Code Live Server หรือ `python3 -m http.server` จะใช้ไม่ได้ เพราะไม่รัน PHP)

#### วิธีที่ 1: (แนะนำ) ใช้ Docker
โปรเจกต์มี `Dockerfile` และ `docker-compose.yml` ให้พร้อมใช้งาน (PHP 8.2 + Apache + MySQL 8.0):

```
cp .env.example .env
docker compose up -d --build
```
จากนั้นเปิดเบราว์เซอร์ไปที่ http://localhost:8080 (แก้พอร์ตได้ที่ `APP_PORT` ใน `.env`)

#### วิธีที่ 2: ใช้ XAMPP
วาง/clone โปรเจกต์ไว้ใน `htdocs` แล้วเปิดใช้งาน Apache + MySQL ผ่าน XAMPP Control Panel จากนั้นสร้างไฟล์ `db-config.php` และฐานข้อมูลตามที่ `api/stats.php` และ `api/track.php` ต้องการ แล้วเปิด http://localhost/vecskill



### 📚 ทักษะ DevOps ที่ครอบคลุม (Core Skills Covered)

**สำหรับผู้เข้าแข่งขัน (ทีม) — เรียงตามลำดับการทำงานจริง:**

* **ภาพรวม:** สถาปัตยกรรม, ผังการเชื่อมต่อ, Quick Start ด้วย VM สำเร็จรูป (VMWare)
* **Server Setup:** การเตรียม Ubuntu Server, Static IP, ผู้ใช้งาน/SSH Key (รวม Windows↔Linux), Firewall (UFW), Docker, Node.js (Online)
* **Containerization:** Portainer, ChartDB (ออกแบบ Database)
* **Network & HTTPS:** Technitium DNS (ภายใน) → Caddy Reverse Proxy + Caddy Internal CA (HTTPS ภายใน), Nginx Proxy Manager (ทางเลือกของ Caddy)
* **Source Control & Registry:** การติดตั้ง Docker Registry + UI, GitLab, การใช้งาน Git เบื้องต้น
* **CI/CD:** GitLab Runner, GitLab CI/CD, ตัวอย่าง Pipeline (2 แบบ)
* **Security & Code Quality:** OWASP ZAP (Security Scan), Trivy (Vulnerability Scan), SonarQube Community Edition (Code Quality), Swagger/OpenAPI (API Docs)
* **IoT (ตามเงื่อนไขของแต่ละสนามแข่งขัน):** Node-RED + MQTT Broker (Mosquitto/EMQX), ตัวอย่าง ESP32 + BME/BMP280

**สำหรับกรรมการ/เจ้าภาพ — เตรียมระบบกลางของสนามแข่งขัน:**

* **Offline Package Mirrors:** Apt-Cacher-Ng, APT Mirror (Ubuntu), DNF Mirror (Rocky/CentOS), Node.js Local Repo (ออฟไลน์), Devpi (Private PyPI), Verdaccio (Private NPM Registry)
* **เกณฑ์การแข่งขัน/ตัวอย่างระบบ:** Hardware/Software ปวช./ปวส., ระบบประเมินครู (PES)

> แต่ละหัวข้อจะระบุไว้ในหน้านั้นๆ ว่าตรงกับเกณฑ์การให้คะแนนของการแข่งขันโดยตรง, เป็นโครงสร้างพื้นฐานที่เจ้าภาพต้องเตรียม, หรือเป็นข้อมูลอ้างอิงเสริมที่ไม่ได้อยู่ในเกณฑ์การให้คะแนน