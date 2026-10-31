<h1>การตั้งค่า Verdaccio (Private NPM Registry)</h1>
<p>Verdaccio เป็น Lightweight Private NPM Proxy Registry ที่สร้างขึ้นด้วย Node.js มันทำหน้าที่เป็น "เซิร์ฟเวอร์ NPM ส่วนตัว" สำหรับเครือข่ายภายในของคุณ</p>
<div class="image-container">
  <p>Verdaccio Web UI แสดงแพ็กเกจที่โฮสต์ไว้</p>
</div>

<h2>1. ทำไมต้องใช้ Verdaccio?</h2>
<p>ในกระบวนการ DevOps การจัดการ Dependencies (แพ็กเกจ) เป็นสิ่งสำคัญ Verdaccio ช่วยแก้ปัญหานี้โดย:</p>
<ul>
  <li>
    <strong>🚀 Caching (ความเร็ว):</strong> เมื่อคุณรัน <code>npm install</code> ครั้งแรก Verdaccio จะดาวน์โหลดแพ็กเกจจาก npmjs.org มาเก็บไว้ใน Cache เมื่อคนในทีมรันคำสั่งเดิมอีกครั้ง
    พวกเขาจะได้รับแพ็กเกจจาก Server ภายใน LAN ของคุณทันที
  </li>
  <li>
    <strong>🔐 Security (ความปลอดภัย):</strong> คุณสามารถสร้างและ Publish "แพ็กเกจส่วนตัว" (Private Packages) ของบริษัท (เช่น <code>@vecskill/common-ui</code>) ใช้งานภายในได้
    โดยไม่ต้องเผยแพร่โค้ดสู่สาธารณะ
  </li>
  <li><strong> offline (การทำงานแบบออฟไลน์):</strong> หาก Server ไม่สามารถเชื่อมต่ออินเทอร์เน็ตได้ <code>npm install</code> จะยังคงทำงานได้สำหรับแพ็กเกจที่ถูก Cache ไว้แล้ว</li>
</ul>

<h2>2. คุณสมบัติเด่น (Features)</h2>
<ul>
  <li>เป็น Private NPM Registry ที่เบา (Lightweight)</li>
  <li>ทำหน้าที่เป็น Proxy และ Cache ให้กับ Public Registry (เช่น npmjs.org)</li>
  <li>มี Web UI ในตัวสำหรับค้นหาและดูรายละเอียดแพ็กเกจ</li>
  <li>รองรับการ Authentication (เริ่มต้นใช้ <code>.htpasswd</code>)</li>
  <li>รองรับ Scoped Packages (เช่น <code>@my-org/package</code>)</li>
</ul>

<h2>3. ข้อจำกัดการใช้งาน (Limitations)</h2>
<ul>
  <li><strong>Storage:</strong> โดยค่าเริ่มต้น Verdaccio จะเก็บแพ็กเกจไว้บนระบบไฟล์ (Filesystem) ซึ่งอาจไม่เหมาะสำหรับระบบที่ต้องการความทนทานสูง (High Availability)</li>
  <li><strong>Authentication:</strong> ระบบยืนยันตัวตนเริ่มต้น (<code>htpasswd</code>) นั้นเรียบง่าย หากต้องการเชื่อมต่อกับ LDAP หรือระบบ Auth ภายนอก อาจต้องใช้ Plugin เพิ่มเติม</li>
</ul>

<hr style="margin: 40px 0" />

<h2>4. การติดตั้ง</h2>
<p>คุณสามารถเลือกติดตั้งได้ 2 วิธีหลัก:</p>

<h3>4.1 วิธีที่ 1: ติดตั้งผ่าน Portainer Stack (แนะนำ)</h3>
<p>วิธีนี้ง่ายที่สุด สะอาด และจัดการได้สะดวกผ่าน Portainer</p>
<h4>4.1.1 สร้าง Docker Volumes</h4>
<pre><code>docker volume create verdaccio_conf
docker volume create verdaccio_storage</code></pre>
<h4>4.1.2 สร้าง Stack (`verdaccio-stack`)</h4>
<p>ไปที่ Portainer > Stacks > + Add stack, ตั้งชื่อ (เช่น `verdaccio-stack`) แล้วใช้โค้ด Docker Compose นี้:</p>
<pre><code>services:
  verdaccio:
    image: verdaccio/verdaccio:latest
    container_name: verdaccio
    restart: unless-stopped
    networks:
      - app_net
    ports:
      - "4873:4873" # Port มาตรฐานของ Verdaccio
    volumes:
      - verdaccio_conf:/verdaccio/conf
      - verdaccio_storage:/verdaccio/storage
    environment:
      - TZ=Asia/Bangkok
      - VERDACCIO_PORT=4873
    
networks:
  app_net:
    external: true
    
volumes:
  verdaccio_conf:
    external: true
  verdaccio_storage:
    external: true
</code></pre>
<p>เมื่อ Deploy สำเร็จ คุณสามารถเข้าถึง Web UI ได้ที่ <code>http://[IP_SERVER_ของคุณ]:4873</code></p>

<hr style="margin: 20px 0" />

<h3>4.2 วิธีที่ 2: ติดตั้งบน Server โดยตรง (Native Install)</h3>
<p>วิธีนี้เหมาะสำหรับผู้ที่ต้องการรัน Verdaccio โดยตรงบน Host OS โดยใช้ Node.js และ Systemd</p>
<h4>4.2.1 ติดตั้ง Node.js (LTS)</h4>
<p>
  Verdaccio ต้องการ Node.js ในการทำงาน แนะนำให้ติดตั้งเวอร์ชัน LTS ล่าสุด ด้วยวิธีเดียวกับหัวข้อ
  <a href="#" onclick="document.querySelector('a[data-page=\'nodejs-install\']').click();">การติดตั้ง Node.js (Online)</a> (ถ้าเครื่องนี้ติดตั้ง Node.js ไว้แล้วจากหัวข้อนั้น ข้ามขั้นตอนนี้ไปได้เลย):
</p>
<pre><code>sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg
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

sudo apt-get update
sudo apt-get install -y nodejs</code></pre>

<h4>4.2.2 ติดตั้ง Verdaccio</h4>
<p>ติดตั้ง Verdaccio แบบ Global โดยใช้ `npm` (เวอร์ชันล่าสุดตอนเขียนคู่มือนี้คือ 7.x เช็กเวอร์ชันปัจจุบันได้ที่ <code>npmjs.com/package/verdaccio</code>):</p>
<pre><code># -g หมายถึงการติดตั้งแบบ Global
sudo npm install -g verdaccio@7</code></pre>

<h4>4.2.3 สร้าง User และโฟลเดอร์</h4>
<p>เพื่อความปลอดภัย เราจะสร้าง User `verdaccio` แยกต่างหากสำหรับรัน Service:</p>
<pre><code># สร้าง user และ group ชื่อ verdaccio (แบบ system)
sudo adduser --system --group --home /srv/verdaccio verdaccio

# สร้างโฟลเดอร์สำหรับเก็บ config, storage (packages), และ htpasswd
sudo mkdir -p /srv/verdaccio/{storage,plugins}
sudo mkdir -p /etc/verdaccio
sudo touch /srv/verdaccio/htpasswd

# โอนสิทธิ์ความเป็นเจ้าของให้ user verdaccio
sudo chown -R verdaccio:verdaccio /srv/verdaccio /etc/verdaccio
</code></pre>

<h4>4.2.4 สร้างไฟล์ Config (`config.yaml`)</h4>
<p>Verdaccio ต้องการไฟล์ Config เพื่อระบุว่าจะเก็บไฟล์ไว้ที่ไหนและจะรับการเชื่อมต่ออย่างไร</p>
<pre><code>sudo nano /etc/verdaccio/config.yaml</code></pre>
<p>คัดลอกเนื้อหาพื้นฐานนี้ไปวาง (สำคัญ: YAML ไวต่อการย่อหน้า):</p>
<pre><code class="language-yaml"># ที่เก็บแพ็กเกจ
storage: /srv/verdaccio/storage
# ที่เก็บปลั๊กอิน
plugins: /srv/verdaccio/plugins

# ตั้งค่า Web UI
web:
  title: Verdaccio Private NPM

# ตั้งค่าการยืนยันตัวตน (ใช้ไฟล์ htpasswd ที่เราสร้างไว้)
auth:
  htpasswd:
    file: /srv/verdaccio/htpasswd
    max_users: 1000

# กำหนด Proxy ไปยัง npmjs.org
uplinks:
  npmjs:
    url: https://registry.npmjs.org/

# กฎการเข้าถึงแพ็กเกจ
packages:
  # แพ็กเกจที่มี @scope (เช่น @vecskill/...)
  '@*/*':
    access: $authenticated
    publish: $authenticated
    proxy: npmjs
  
  # แพ็กเกจทั่วไป
  '**':
    access: $all
    publish: $authenticated
    proxy: npmjs

# Port ที่จะให้ Service ทำงาน (รับการเชื่อมต่อจากทุก IP)
listen: 0.0.0.0:4873
</code></pre>

<h4>4.2.5 สร้าง Systemd Service File</h4>
<p>สร้างไฟล์ Service เพื่อให้ `systemd` จัดการการเริ่ม/หยุด Service ได้</p>
<pre><code>sudo nano /etc/systemd/system/verdaccio.service</code></pre>
<p>คัดลอกเนื้อหานี้ไปวาง:</p>
<pre><code>[Unit]
Description=Verdaccio Private NPM Registry
After=network.target

[Service]
User=verdaccio
Group=verdaccio
# ระบุตำแหน่งไฟล์ config ผ่าน Environment Variable
Environment=VERDACCIO_CONFIG_PATH=/etc/verdaccio/config.yaml
# หาตำแหน่งที่ติดตั้ง verdaccio (ปกติจะอยู่ที่ /usr/bin/verdaccio หรือ /usr/lib/node_modules/verdaccio/bin/verdaccio)
ExecStart=/usr/bin/verdaccio --config ${VERDACCIO_CONFIG_PATH}
Restart=on-failure

[Install]
WantedBy=multi-user.target
</code></pre>

<h4>4.2.6 เปิดใช้งาน Service</h4>
<pre><code>sudo systemctl daemon-reload
sudo systemctl enable --now verdaccio
sudo systemctl status verdaccio</code></pre>
<p>หาก Service ทำงาน (active/running) คุณจะสามารถเข้าถึง Web UI ได้ที่ <code>http://[IP_SERVER_ของคุณ]:4873</code> เช่นกัน</p>

<hr style="margin: 40px 0" />

<h2>5. (แนะนำ) การใช้งานร่วมกับ Caddy</h2>
<p>
  เพื่อให้เข้าถึงได้ง่ายและปลอดภัยด้วย HTTPS ภายใน ให้เพิ่ม Block นี้ใน <code>/opt/caddy/Caddyfile</code> ของคุณ (จะใช้
  <a href="#" onclick="document.querySelector('a[data-page=\'npm-proxy\']').click();">Nginx Proxy Manager</a> แทนก็ได้ หลักการเดียวกัน แค่ชี้ Forward Hostname/Port ไปที่ <code>verdaccio:4873</code>
  หรือ <code>127.0.0.1:4873</code> ตามวิธีที่ติดตั้ง)
</p>
<p>
  <strong>เลือก <code>reverse_proxy</code> ให้ตรงกับวิธีติดตั้งของคุณ:</strong>
</p>
<pre><code># ---------- NPM (Verdaccio) ----------
npm.vecskill.ovec {
	tls internal
	encode zstd gzip
	header {
		Strict-Transport-Security "max-age=31536000;"
	}
	log {
		output file /var/log/caddy/npm_access.log
	}
    
    # --- หากติดตั้งด้วย Docker Stack (วิธีที่ 1) ---
	reverse_proxy http://verdaccio:4873

    # --- หากติดตั้งแบบ Native (วิธีที่ 2) ---
    # reverse_proxy http://127.0.0.1:4873
}
</code></pre>
<p>อย่าลืม Reload Caddy และตั้งค่า DNS ของคุณให้ <code>npm.vecskill.ovec</code> ชี้มาที่ Caddy Server</p>

<hr style="margin: 40px 0" />

<h2>6. การตั้งค่าฝั่ง Client (เครื่องนักพัฒนา)</h2>
<p>หลังจาก Server พร้อมใช้งานแล้ว เครื่อง Client (เช่น คอมพิวเตอร์ของคุณ หรือ GitLab Runner) ต้องถูกตั้งค่าให้ชี้มาที่ Verdaccio Server แทน npmjs.org</p>

<h3>6.1 (สำคัญ) ติดตั้ง Root CA ของ Caddy</h3>
<p>
  หากคุณใช้ Caddy <code>tls internal</code> (HTTPS ภายใน) คุณต้องติดตั้งไฟล์ <code>caddy-root.crt</code> ลงใน Trusted Root Store ของเครื่อง Client ก่อน ไม่เช่นนั้น Client จะฟ้อง lỗi SSL
  (<code>SELF_SIGNED_CERT_IN_CHAIN</code>)
</p>
<ul>
  <li><strong>Windows:</strong> <code>Import-Certificate -FilePath "C:\path\to\caddy-root.crt" -CertStoreLocation Cert:\LocalMachine\Root</code> (Run as Admin)</li>
  <li><strong>Linux:</strong> <code>sudo cp caddy-root.crt /usr/local/share/ca-certificates/</code> แล้วรัน <code>sudo update-ca-certificates</code></li>
</ul>

<h3>6.2 ตั้งค่า NPM Registry และ Login</h3>
<p>รันคำสั่งนี้ใน Terminal ของเครื่อง Client เพื่อบอกให้ `npm` ใช้ Verdaccio Server ของคุณเป็น Registry หลัก:</p>
<pre><code># (ถ้าใช้ HTTPS ภายในกับ Caddy)
npm set registry https://npm.vecskill.ovec

# (ถ้าเข้าผ่าน IP ตรง และใช้ HTTP)
# npm set registry http://10.20.30.101:4873
</code></pre>

<h3>6.3 สร้าง User (ทำครั้งแรก)</h3>
<p>ในการ Publish แพ็กเกจส่วนตัว คุณต้องสร้าง User บน Verdaccio ก่อน</p>
<pre><code># --registry ชี้ไปที่ server ของคุณ
npm adduser --registry https://npm.vecskill.ovec</code></pre>
<p>ระบบจะถามให้คุณตั้งค่า: Username, Password, และ Email</p>

<h3>6.4 การใช้งาน</h3>
<ul>
  <li>
    <strong>การติดตั้งแพ็กเกจ (Install):</strong>
    <pre><code># npm จะดึงจาก Verdaccio (ถ้ามี Cache) หรือดึงจาก npmjs.org ผ่าน Verdaccio
npm install express
npm install lodash</code></pre>
  </li>
  <li>
    <strong>การเผยแพร่แพ็กเกจส่วนตัว (Publish):</strong>
    <pre><code># เข้าไปที่โฟลเดอร์โปรเจกต์ Node.js ของคุณ แล้วรัน:
npm publish</code></pre>
  </li>
</ul>

<h3>6.5 การสลับกลับไปใช้ Registry สาธารณะ</h3>
<p>หากคุณต้องการสลับกลับไปใช้ npmjs.org ชั่วคราว ให้รัน:</p>
<pre><code>npm set registry https://registry.npmjs.org/
</code></pre>
