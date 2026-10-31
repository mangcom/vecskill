<h1>การตั้งค่า devpi (Private PyPI Server)</h1>
<p>
    Devpi เป็น PyPI (Python Package Index) Server ที่มีความสามารถหลากหลาย ช่วยให้เราสามารถ:
</p>
<ul>
    <li>Mirror และ Cache แพ็กเกจจาก PyPI สาธารณะ ทำให้การติดตั้งแพ็กเกจ Python (`pip install`) เร็วขึ้นและเสถียรขึ้นภายในเครือข่ายของเรา</li>
    <li>สร้าง Index ส่วนตัวสำหรับโฮสต์แพ็กเกจ Python ที่เราพัฒนาขึ้นเอง</li>
    <li>จัดการ User และสิทธิ์การเข้าถึงสำหรับ Index ต่างๆ</li>
</ul>
<p>
    ในคู่มือนี้ เราจะติดตั้ง `devpi` เป็น Service บน Server Ubuntu ของเรา (สมมติ IP: <code>10.20.30.101</code> และต้องการเข้าถึงผ่าน <code>pypi.vecskill.ovec</code>)
</p>

<h3>แนวคิดหลัก</h3>
<ul>
    <li>เราจะสร้าง User เฉพาะ (`devpi`) สำหรับรัน Service นี้</li>
    <li>ติดตั้ง `devpi-server` และ `devpi-web` (Web UI) ภายใน Python Virtual Environment (venv) เพื่อความสะอาด</li>
    <li>ตั้งค่าให้ `devpi` ทำงานเป็น Systemd Service เพื่อให้เริ่มทำงานอัตโนมัติ</li>
    <li>สร้าง Index หลัก `root/pypi` สำหรับ Mirror แพ็กเกจสาธารณะ</li>
    <li>สร้าง User (`teacher`) และ Index ส่วนตัว (`teacher/dev`) สำหรับเก็บแพ็กเกจภายใน</li>
    <li>(แนะนำ) ใช้ Caddy เป็น Reverse Proxy เพื่อเข้าถึง `devpi` ผ่าน Domain Name (<code>pypi.vecskill.ovec</code>) และเพิ่ม HTTPS</li>
</ul>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 1: การติดตั้งและตั้งค่าบน Server (Ubuntu)</h2>

<h3>1.1 เตรียมผู้ใช้และโฟลเดอร์</h3>
<p>สร้าง User `devpi` แบบ System Account และสร้างโฟลเดอร์สำหรับเก็บข้อมูล:</p>
<pre><code># สร้าง user และ group ชื่อ devpi
sudo adduser --system --group --home /srv/devpi devpi

# สร้างโฟลเดอร์สำหรับข้อมูล server, virtualenv, และ backup
sudo mkdir -p /srv/devpi/{server,venv,backup}

# กำหนดสิทธิ์ให้ user devpi เป็นเจ้าของ
sudo chown -R devpi:devpi /srv/devpi</code></pre>

<h3>1.2 ติดตั้ง Python และสร้าง Virtual Environment (venv)</h3>
<p>ตรวจสอบว่ามี Python 3 และ `python3-venv` ติดตั้งอยู่:</p>
<pre><code>sudo apt update
sudo apt install -y python3-venv</code></pre>
<p>สร้าง venv และติดตั้งแพ็กเกจ `devpi` โดยใช้ User `devpi`:</p>
<pre><code>sudo -u devpi bash -lc '
python3 -m venv /srv/devpi/venv
/srv/devpi/venv/bin/pip install -U pip
/srv/devpi/venv/bin/pip install devpi-server devpi-web devpi-client
'</code></pre>

<h3>1.3 Initialise โครงสร้างเซิร์ฟเวอร์ (ทำครั้งเดียว)</h3>
<pre><code>sudo -u devpi /srv/devpi/venv/bin/devpi-init --serverdir /srv/devpi/server</code></pre>

<h3>1.4 สร้าง Systemd Service File</h3>
<p>สร้างไฟล์ <code>/etc/systemd/system/devpi.service</code>:</p>
<pre><code>sudo nano /etc/systemd/system/devpi.service</code></pre>
<p>วางเนื้อหานี้:</p>
<pre><code>[Unit]
Description=Devpi PyPI mirror & private index
After=network.target

[Service]
User=devpi
Group=devpi
Environment=OUTSIDE_URL=http://pypi.vecskill.ovec # แก้ไข URL นี้ให้ถูกต้อง
ExecStart=/srv/devpi/venv/bin/devpi-server \
  --serverdir /srv/devpi/server \
  --host 0.0.0.0 \
  --port 3141 \
  --mirror-cache-expiry 3600 \
  --outside-url ${OUTSIDE_URL}
Restart=on-failure
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
</code></pre>

<h3>1.5 เปิดใช้งาน Service</h3>
<pre><code>sudo systemctl daemon-reload
sudo systemctl enable --now devpi
sudo systemctl status devpi
# ทดสอบ
curl -fsS http://127.0.0.1:3141/+status</code></pre>

<h3>1.6 ตั้งรหัสผ่าน Root และสร้าง User/Index</h3>
<p>ใช้ `devpi-client` เพื่อตั้งค่า:</p>
<pre><code># ใช้ devpi client
sudo -u devpi /srv/devpi/venv/bin/devpi use http://localhost:3141

# ล็อกอิน root ครั้งแรก (ไม่มีรหัส)
sudo -u devpi /srv/devpi/venv/bin/devpi login root --password ""

# ตั้งรหัส root (เปลี่ยนรหัสผ่าน!)
sudo -u devpi /srv/devpi/venv/bin/devpi user -m root password "StrongRoot#2025!"

# --- สร้าง User และ Index ส่วนตัว ---
# ล็อกอิน root ด้วยรหัสใหม่
sudo -u devpi /srv/devpi/venv/bin/devpi login root --password "StrongRoot#2025!"

# สร้าง user 'teacher' (เปลี่ยนรหัสผ่าน/อีเมล)
sudo -u devpi /srv/devpi/venv/bin/devpi user -c teacher password "T3acher#2025" email "teacher@example.com"

# ล็อกอิน teacher
sudo -u devpi /srv/devpi/venv/bin/devpi login teacher --password "T3acher#2025"

# สร้าง index 'teacher/dev' สืบทอดจาก 'root/pypi'
sudo -u devpi /srv/devpi/venv/bin/devpi index -c teacher/dev bases=root/pypi volatile=true
</code></pre>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 2: การตั้งค่า Reverse Proxy ด้วย Caddy (แนะนำ)</h2>
<p>
    เพื่อให้เข้าถึง `devpi` ผ่าน Domain Name (<code>pypi.vecskill.ovec</code>) และใช้งาน HTTPS ภายในได้สะดวก แนะนำให้ตั้งค่า Caddy ดังนี้:
</p>
<p>เปิดไฟล์ `/opt/caddy/Caddyfile` ของคุณ แล้วเพิ่ม Block นี้เข้าไป:</p>
<pre><code># ---------- PyPI (devpi) ----------
pypi.vecskill.ovec {
	tls internal  # ใช้ Internal CA ของ Caddy (ถ้าต้องการ HTTPS ภายใน)
	# หากไม่ใช้ HTTPS ภายใน ให้ลบบรรทัด tls internal ออก
	encode zstd gzip
	reverse_proxy http://127.0.0.1:3141 { # ชี้ไปที่ devpi ที่รันบน port 3141
		# Devpi ต้องการ Header เหล่านี้เพื่อสร้าง URL ให้ถูกต้อง
		header_up X-Forwarded-Proto {scheme}
		header_up X-Outside-Url {scheme}://{host}
	}
}
</code></pre>
<p>จากนั้น Reload Caddy:</p>
<pre><code># หากรัน Caddy เป็น Service
sudo systemctl reload caddy

# หากรันผ่าน Docker Stack (ชื่อ caddy-internal)
docker restart caddy
</code></pre>
<p>
    หลังจากนี้ คุณจะสามารถเข้าถึง Web UI ของ `devpi` ได้ที่ <code>http://pypi.vecskill.ovec</code> หรือ <code>https://pypi.vecskill.ovec</code> (หากใช้ `tls internal` และติดตั้ง Root CA บน Client แล้ว)
</p>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 3: การตั้งค่าเครื่อง Client (Windows และ Linux)</h2>
<p>
    เพื่อให้ `pip` บนเครื่อง Client รู้จัก `devpi` Server เราต้องสร้าง/แก้ไขไฟล์คอนฟิกของ `pip`
</p>

<h3>3.1 Client Setup (Windows)</h3>
<p>รันคำสั่งต่อไปนี้ใน PowerShell:</p>
<ol>
    <li>
        <strong>ติดตั้ง/ตรวจสอบ Python:</strong>
        <pre><code class="language-powershell"># ติดตั้ง Python 3.12 (ถ้ายังไม่มี)
winget install --id Python.Python.3.12 -e
# ตรวจสอบเวอร์ชัน
py -V
py -m pip -V</code></pre>
    </li>
    <li>
        <strong>ทดสอบการเชื่อมต่อ:</strong>
        <pre><code class="language-powershell">nslookup pypi.vecskill.ovec
# ทดสอบ Port (เลือกอันที่ตรงกับ Caddy config ของคุณ)
Test-NetConnection pypi.vecskill.ovec -Port 443 # HTTPS
# Test-NetConnection pypi.vecskill.ovec -Port 80  # HTTP</code></pre>
    </li>
    <li>
        <strong>ตั้งค่า `pip.ini` (โหมด Mirror: `root/pypi`):</strong>
        <pre><code class="language-powershell">$pipDir="$env:APPDATA\pip"; if(!(Test-Path $pipDir)){New-Item -ItemType Directory $pipDir | Out-Null}
@"
[global]
index-url = http://pypi.vecskill.ovec/root/pypi/+simple/
trusted-host = pypi.vecskill.ovec
timeout = 60
"@ | Set-Content "$pipDir\pip.ini" -Encoding ASCII</code></pre>
    </li>
    <li>
        <strong>(ทางเลือก) ตั้งค่า `pip.ini` (Private Index: `teacher/dev`):</strong>
        <pre><code class="language-powershell">$user="teacher"; $password=[System.Uri]::EscapeDataString("T3acher#2025") # ใส่รหัสผ่านของคุณ
$pipDir="$env:APPDATA\pip"; if(!(Test-Path $pipDir)){New-Item -ItemType Directory $pipDir | Out-Null}
@"
[global]
index-url = http://$($user):$($password)@pypi.vecskill.ovec/teacher/dev/+simple/
trusted-host = pypi.vecskill.ovec
timeout = 60
"@ | Set-Content "$pipDir\pip.ini" -Encoding ASCII</code></pre>
    </li>
    <li>
        <strong>(ถ้าใช้ HTTPS Internal CA) ติดตั้ง Root CA และแก้ไข `pip.ini`:</strong>
        <pre><code class="language-powershell"># 1. ติดตั้ง Root CA (รัน PowerShell เป็น Admin)
Import-Certificate -FilePath "C:\Users\Public\caddy-root.crt" -CertStoreLocation Cert:\LocalMachine\Root

# 2. แก้ไข pip.ini ให้ใช้ HTTPS URL และเพิ่ม cert path
$pipDir="$env:APPDATA\pip"
@"
[global]
index-url = https://pypi.vecskill.ovec/root/pypi/+simple/
trusted-host = pypi.vecskill.ovec
cert = C:\Users\Public\caddy-root.crt # แก้ไข Path ให้ถูกต้อง
timeout = 60
"@ | Set-Content "$pipDir\pip.ini" -Encoding ASCII
</code></pre>
    </li>
    <li>
        <strong>ทดสอบติดตั้ง:</strong>
        <pre><code class="language-powershell">py -m pip install requests</code></pre>
    </li>
</ol>

<h3>3.2 Client Setup (Linux - Ubuntu/Debian)</h3>
<p>ตั้งค่าผ่านไฟล์ `~/.config/pip/pip.conf`:</p>
<ol>
    <li>
        <strong>ติดตั้ง/ตรวจสอบ Python & pip:</strong> (มักจะมีอยู่แล้ว)
        <pre><code>python3 -V
python3 -m pip -V</code></pre>
    </li>
    <li>
        <strong>ทดสอบการเชื่อมต่อ:</strong>
        <pre><code>nslookup pypi.vecskill.ovec
curl -I http://pypi.vecskill.ovec/ # หรือ https:// ถ้าใช้ HTTPS</code></pre>
    </li>
    <li>
        <strong>ตั้งค่า `pip.conf` (โหมด Mirror: `root/pypi`):</strong>
        <pre><code># สร้างโฟลเดอร์ config ถ้ายังไม่มี
mkdir -p ~/.config/pip

# สร้าง/เขียนทับไฟล์ pip.conf
cat << EOF > ~/.config/pip/pip.conf
[global]
index-url = http://pypi.vecskill.ovec/root/pypi/+simple/
trusted-host = pypi.vecskill.ovec
timeout = 60
EOF</code></pre>
    </li>
    <li>
        <strong>(ทางเลือก) ตั้งค่า `pip.conf` (Private Index: `teacher/dev`):</strong>
        <pre><code># สร้างโฟลเดอร์ config ถ้ายังไม่มี
mkdir -p ~/.config/pip

# สร้าง/เขียนทับไฟล์ pip.conf (ต้อง URL Encode Password ถ้ามีอักขระพิเศษ)
# ใช้ python ช่วย encode: python3 -c 'import urllib.parse; print(urllib.parse.quote("T3acher#2025"))'
USER="teacher"
ENCODED_PASS="T3acher%232025" # ผลลัพธ์จากการ Encode

cat << EOF > ~/.config/pip/pip.conf
[global]
index-url = http://${USER}:${ENCODED_PASS}@pypi.vecskill.ovec/teacher/dev/+simple/
trusted-host = pypi.vecskill.ovec
timeout = 60
EOF</code></pre>
    </li>
    <li>
        <strong>(ถ้าใช้ HTTPS Internal CA) ติดตั้ง Root CA และแก้ไข `pip.conf`:</strong>
        <pre><code># 1. คัดลอก caddy-root.crt มาไว้ที่ /usr/local/share/ca-certificates/
sudo cp /path/to/caddy-root.crt /usr/local/share/ca-certificates/caddy-local-ca.crt

# 2. อัปเดต CA store ของระบบ
sudo update-ca-certificates

# 3. แก้ไข pip.conf ให้ใช้ HTTPS URL (ไม่ต้องระบุ cert เพราะระบบรู้จัก CA แล้ว)
cat << EOF > ~/.config/pip/pip.conf
[global]
index-url = https://pypi.vecskill.ovec/root/pypi/+simple/
trusted-host = pypi.vecskill.ovec
timeout = 60
EOF</code></pre>
    </li>
    <li>
        <strong>ทดสอบติดตั้ง:</strong>
        <pre><code>python3 -m pip install requests</code></pre>
    </li>
</ol>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 4: การสำรองและกู้คืนข้อมูล</h2>

<h3>4.1 การสำรองข้อมูล (Export)</h3>
<p>รันคำสั่งนี้บน Server:</p>
<pre><code>sudo -u devpi /srv/devpi/venv/bin/devpi-server --export /srv/devpi/backup/export-$(date +%F)</code></pre>

<h3>4.2 การกู้คืนข้อมูล (Import)</h3>
<ol>
    <li>ติดตั้ง `devpi` บน Server ใหม่ (ขั้นตอน 1.1 - 1.5)</li>
    <li>คัดลอกโฟลเดอร์ Backup ไปที่ `/srv/devpi/backup`</li>
    <li>หยุด Service `devpi`, รัน Import, แล้วเริ่ม Service ใหม่:
        <pre><code>sudo systemctl stop devpi
sudo -u devpi /srv/devpi/venv/bin/devpi-server --serverdir /srv/devpi/server --import /srv/devpi/backup/export-YYYY-MM-DD
sudo systemctl start devpi</code></pre>
    </li>
</ol>

<hr style="margin: 40px 0;">

<h2>ส่วนที่ 5: แก้ไขปัญหาเบื้องต้น และ เคล็ดลับ</h2>

<h3>แก้ไขปัญหา (Troubleshooting)</h3>
<ul>
    <li><strong>401/403 (Client):</strong> URL ของ Private Index ใน `pip.conf`/`pip.ini` ไม่มี User/Pass หรือพิมพ์ผิด (อย่าลืม URL-Encode Password)</li>
    <li><strong>SSL Error (Client):</strong> CERTIFICATE_VERIFY_FAILED:
        <ul>
            <li>**Windows:** ติดตั้ง Root CA + เพิ่ม `cert = C:\path\to\root-ca.crt` ใน `pip.ini`</li>
            <li>**Linux:** ติดตั้ง Root CA ด้วย `update-ca-certificates` (ไม่ต้องเพิ่ม `cert=` ใน `pip.conf`)</li>
        </ul>
    </li>
    <li><strong>Could not find a version (Client):** ชี้ `index-url` ผิด Index หรือแพ็กเกจยังไม่เคยถูก Mirror → ลองติดตั้งอีกครั้ง</li>
    <li><strong>`pip` ยังวิ่งออกเน็ต (Client):** ตรวจสอบ `pip config debug` (Windows) หรือ `pip config list` (Linux) ให้แน่ใจว่า `index-url` ถูกต้อง และไม่มี `--extra-index-url` อื่น</li>
    <li><strong>Service ไม่เริ่ม (Server):** ตรวจสอบ Log ด้วย `sudo journalctl -u devpi -f`, ตรวจสอบสิทธิ์ของโฟลเดอร์ `/srv/devpi`, ตรวจสอบว่า Port 3141 ไม่ถูกใช้งานโดยโปรแกรมอื่น</li>
</ul>

<h3>เคล็ดลับ (Tips)</h3>
<ul>
    <li>ใช้ <code>--mirror-cache-expiry</code> ดีกว่า <code>--refresh</code></li>
    <li>ตั้งค่า <code>--outside-url</code> (ใน Service) หรือ `X-Outside-Url` (ใน Caddy) ให้ถูกต้องเสมอ</li>
    <li>ถ้าใช้ Caddy ไม่ต้องเปิด Port 3141 ที่ Firewall สู่ภายนอก</li>
    <li>**Pre-warm Cache:** ดาวน์โหลดแพ็กเกจล่วงหน้าด้วย `pip download` (ดูตัวอย่างใน Client Setup)</li>
</ul>