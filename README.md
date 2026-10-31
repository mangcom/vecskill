# IT VECSkill Guide: คู่มือเตรียมความพร้อมการแข่งขันทักษะ DevOps

"IT VECSkill Guide" คือโปรเจกต์คู่มือออนไลน์ (Single Page Application) ที่สร้างด้วย HTML, CSS และ JavaScript พื้นฐาน โดยมีจุดประสงค์หลักเพื่อใช้เป็น **คู่มือเตรียมความพร้อมสำหรับการแข่งขันทักษะวิชาชีพ** (เช่น ทักษะเทคโนโลยีสารสนเทศด้านโปรแกรมและเครือข่ายด้วยกระบวนการ DevOps )

คู่มือนี้ถูกออกแบบมาเพื่อ 2 กลุ่มเป้าหมายหลัก:

1.  **สำหรับผู้เข้าแข่งขัน (ทีม):**
    * ใช้เป็น **"คู่มือฝึกซ้อม" (Training Guide)** ที่สมบูรณ์แบบ
    * สามารถฝึกฝนการติดตั้งและตั้งค่าเครื่องมือ DevOps ที่จำเป็นทั้งหมดได้ด้วยตนเอง ตั้งแต่การเตรียม Server, Docker, GitLab, Private Registries (Docker, PyPI), การตั้งค่า DNS ภายใน (Technitium) และการทำ HTTPS ภายใน (Caddy)

2.  **สำหรับเจ้าภาพ (ผู้จัดการแข่งขัน):**
    * ใช้เป็น **"คู่มืออ้างอิง" (Host's Playbook)**
    * ช่วยให้คณะกรรมการสามารถเตรียมสภาพแวดล้อม Server กลางสำหรับการแข่งขัน (เช่น Container Registry Server หรือ Git Server ได้อย่างถูกต้องและเป็นมาตรฐานเดียวกัน

<div align="center">
  <img src="https://vecskill.bncc.ac.th/images/00-visio.png" alt="Project Architecture Diagram" width="700">
</div>

---

## 🚀 คุณสมบัติของหน้าเว็บ (Features)

* **Responsive Design:** รองรับการแสดงผลบนทุกอุปกรณ์ (Desktop/Mobile)
* **Dynamic Content Loading:** โหลดเนื้อหาแบบไดนามิกโดยใช้ JavaScript `fetch()` (ไม่โหลดหน้าใหม่)
* **Collapsible Sidebar:** เมนูที่สามารถพับเก็บ/ขยายได้ (ทั้งแบบอัตโนมัติบนมือถือ และแบบ Manual บน Desktop)
* **Copy Code Button:** ปุ่มคัดลอกโค้ดอัตโนมัติในทุกโค้ดบล็อก (`<pre>`)
* **View Counter:** ระบบนับจำนวนการเข้าชมหน้าแต่ละหน้า (เก็บใน `localStorage`)
* **Embedded Content:** รองรับการฝังเนื้อหาจากภายนอก เช่น Google Drive `<iframe>`

## 💻 เทคโนโลยีที่ใช้

* **HTML5**
* **CSS3** (Flexbox, Grid, Media Queries)
* **Vanilla JavaScript (ES6+)** (Fetch API, DOM Manipulation, LocalStorage)
* **Font Awesome** (สำหรับไอคอน)

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
ภาพรวม: สถาปัตยกรรม, ผังการเชื่อมต่อ, Google Drive Embed

Server Setup: การเตรียม Ubuntu Server, Static IP, Firewall (UFW)

Containerization: การติดตั้ง Docker, Portainer

DevOps Tools: GitLab, Git (Basic)

Internal Services: Technitium DNS, Caddy (Internal CA), Apt-Cacher-Ng, Devpi (Private PyPI)

ตัวอย่าง: ระบบประเมินครู (PES), เกณฑ์การแข่งขัน (ปวช./ปวส.), ตัวอย่าง IoT (ESP32 + MQTT)

(กำลังพัฒนา): GitLab CI/CD, GitLab Runner,Verdaccio, Nginx Proxy Manager, Cloudflare