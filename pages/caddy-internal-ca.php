<h1>การตั้งค่า Caddy เป็น Internal CA (สำหรับ HTTPS ภายใน)</h1>
<p>
    หน้านี้เป็นภาคต่อของบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-proxy\']').click();">Caddy Reverse Proxy</a> ส่วนที่ 1 ซึ่งแนะนำให้ใช้ความสามารถของ Caddy ในการทำหน้าที่เป็น
    <strong>Certificate Authority (CA) ภายใน</strong> ของตัวเองสำหรับ Service ที่ต้องการให้เข้าถึงได้เฉพาะเครื่องในทีม (Intranet) เท่านั้น โดยไม่ต้องมีโดเมนสาธารณะหรือขอ Certificate จาก Let's Encrypt
</p>
<p>
    Caddy สามารถออก SSL Certificate ที่ถูกต้องและน่าเชื่อถือ *สำหรับใช้งานภายในเครือข่ายของคุณ* ได้โดยอัตโนมัติ ทำให้คุณสามารถเข้าถึง Service ต่างๆ เช่น <code>https://gitlab.vecskill.ovec</code> หรือ <code>https://portainer.vecskill.ovec</code> ด้วย HTTPS โดยไม่มีคำเตือนเรื่องความปลอดภัยจากเบราว์เซอร์เลย! หน้านี้จะแสดงตัวอย่าง Caddyfile แบบเต็มสำหรับทุก Service ในคู่มือนี้ และขั้นตอนติดตั้ง Root CA บนเครื่อง Client
</p>
<p>
    วิธีนี้เหมาะอย่างยิ่งสำหรับการแข่งขันหรือเครือข่ายภายในองค์กรที่ไม่ต้องการเปิดเผย Service สู่โลกภายนอก (ถ้าใช้ Nginx Proxy Manager แทน Caddy ดูวิธีทำ Intranet HTTPS ที่เทียบเท่ากันด้วย <code>mkcert</code>
    ได้ที่บท <a href="#" onclick="document.querySelector('a[data-page=\'npm-proxy\']').click();">Nginx Proxy Manager</a> ส่วนที่ 3)
</p>

<h3>แนวคิดหลัก</h3>
<ol>
    <li>เราจะตั้งค่า Caddy ให้ใช้โหมด "Internal CA"</li>
    <li>เราจะกำหนดค่า Reverse Proxy ให้กับ Service ภายในของเราใน `Caddyfile` โดยใช้ Directive `tls internal`</li>
    <li>Caddy จะสร้าง Root CA ของตัวเองขึ้นมา และใช้ CA นั้นในการออก Certificate ให้กับโดเมนภายในที่เรากำหนด</li>
    <li>เพื่อให้เบราว์เซอร์บนเครื่อง Client (เช่น คอมพิวเตอร์ของคุณ) เชื่อถือ Certificate เหล่านี้ เราต้องนำ Root CA ของ Caddy ไปติดตั้ง (Import) ใน Trusted Root Store ของเครื่อง Client เพียงครั้งเดียว</li>
</ol>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 1: การตั้งค่า Caddyfile บน Server</h2>
<p>
    เราจะสร้างไฟล์คอนฟิกหลักสำหรับ Caddy ซึ่งจะบอกว่าให้ทำหน้าที่เป็น Internal CA และกำหนดค่า Reverse Proxy สำหรับ Service ต่างๆ ของเรา
</p>
<ol>
    <li>
        <strong>สร้างโฟลเดอร์สำหรับ Caddy:</strong>
        <pre><code>sudo mkdir -p /opt/caddy</code></pre>
    </li>
    <li>
        <strong>สร้างและแก้ไข `Caddyfile`:</strong>
        <pre><code>sudo nano /opt/caddy/Caddyfile</code></pre>
    </li>
    <li>
        <strong>คัดลอกเนื้อหาคอนฟิกนี้ไปวาง:</strong> (อย่าลืมเปลี่ยนชื่อโดเมน <code>*.vecskill.ovec</code> ให้เป็นชื่อโดเมนภายในที่คุณต้องการใช้)
        <pre><code>{
	# Global Options Block: สั่งให้ Caddy ออก Certificate จาก Internal CA ของตัวเองให้ทุกโดเมนในไฟล์นี้
	# โดยอัตโนมัติ (แทนที่จะขอจาก Let's Encrypt) ไม่ต้องใส่ tls internal ซ้ำในทุก Site Block ด้านล่าง
	local_certs
	# auto_https on  # เปิดใช้งาน HTTPS อัตโนมัติ (เป็นค่าเริ่มต้นอยู่แล้ว)
}

# ---------- Technitium DNS (Web Admin) ----------
dns.vecskill.ovec {
	encode zstd gzip
	reverse_proxy http://technitium-dns:5380
}

# ---------- Portainer ----------
# (ตัวอย่างนี้สำหรับ Portainer ที่รันบน HTTPS Port 9443)
portainer.vecskill.ovec {
	encode zstd gzip
	reverse_proxy https://portainer:9443 {
		transport http {
			tls_insecure_skip_verify # จำเป็นเพราะ Certificate ของ Portainer เป็น Self-signed
		}
	}
}

# ---------- Docker Registry (Distribution v2) ----------
registry.vecskill.ovec {
	encode zstd gzip
	header Docker-Distribution-Api-Version "registry/2.0" # Header เฉพาะของ Registry
	reverse_proxy http://registry:5000 {
		header_up X-Forwarded-Proto {scheme}
	}
}

# ---------- Registry UI ----------
registry-ui.vecskill.ovec {
	encode zstd gzip
	reverse_proxy http://registry-ui:80 {
		header_up X-Forwarded-Proto {scheme}
	}
}

# ---------- NPM (Verdaccio - สมมติว่ามี Container ชื่อ verdaccio) ----------
npm.vecskill.ovec {
	encode zstd gzip
	header Strict-Transport-Security "max-age=31536000;"
	log {
		output file /var/log/caddy/npm_access.log
	}
	reverse_proxy http://verdaccio:4873
}

# ---------- PyPI (devpi - สมมติว่ามี Container ชื่อ devpi) ----------
pypi.vecskill.ovec {
	encode zstd gzip
	reverse_proxy http://devpi:3141 {
		header_up X-Forwarded-Proto {scheme}
		header_up X-Outside-Url {scheme}://{host} # Header เฉพาะของ devpi
	}
}

# ---------- APT cacher (สมมติว่ามี Container ชื่อ aptcacher) ----------
apt.vecskill.ovec {
	encode zstd gzip
	reverse_proxy http://aptcacher:3142
}

# ---------- GitLab ----------
gitlab.vecskill.ovec {
	encode zstd gzip
	header Strict-Transport-Security "max-age=31536000;"
	log {
		output file /var/log/caddy/gitlab_access.log
	}
	reverse_proxy http://gitlab:80 {
		header_up X-Forwarded-Proto {scheme}
	}
}
</code></pre>
        <p>กด `Ctrl+X` > `Y` > `Enter` เพื่อบันทึกไฟล์</p>
    </li>
</ol>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 2: สร้าง Volume และ Deploy Caddy ผ่าน Stack</h2>
<p>
    เราจะสร้าง Volume ที่จำเป็นและรัน Caddy Container โดยใช้ Docker Compose ผ่าน Portainer Stack
</p>
<ol>
    <li>
        <strong>สร้าง Docker Volumes:</strong> (หากยังไม่ได้สร้าง Network <code>app_net</code> ให้สร้างด้วย <code>docker network create app_net</code> ก่อน)
        <pre><code>docker volume create caddy_data
docker volume create caddy_config</code></pre>
        <em>หมายเหตุ: Volume <code>caddy_config</code> จะใช้เก็บ Root CA ที่ Caddy สร้างขึ้น</em>
    </li>
    <li>
        <strong>สร้างโฟลเดอร์สำหรับ Log บน Host Server ก่อน Deploy:</strong>
        <pre><code>sudo mkdir -p /var/log/caddy</code></pre>
    </li>
    <li>
        <strong>สร้าง Stack ใน Portainer:</strong><br>
        ไปที่ Portainer > Stacks > + Add stack, ตั้งชื่อ (เช่น `caddy-internal`) แล้วใช้โค้ด Docker Compose นี้:
        <pre><code>services:
  caddy:
    image: caddy:latest # ใช้เวอร์ชันล่าสุด หรือระบุเวอร์ชัน 2.x.x
    container_name: caddy
    restart: unless-stopped
    networks:
      - app_net
    ports:
      - "80:80"       # สำหรับ Redirect HTTP -> HTTPS
      - "443:443"     # HTTPS หลัก
      - "443:443/udp" # HTTP/3 (ถ้าต้องการ)
    volumes:
      # จุดสำคัญ: Mount Caddyfile จาก Host เข้าไปเป็น Read-Only
      - /opt/caddy/Caddyfile:/etc/caddy/Caddyfile:ro 
      # Volume สำหรับเก็บ Certificate และข้อมูลอื่นๆ
      - caddy_data:/data 
      # Volume สำหรับเก็บ Config และ Root CA
      - caddy_config:/config 
      # Volume สำหรับเก็บ Log (สร้างโฟลเดอร์ /var/log/caddy บน Host ก่อน)
      - /var/log/caddy:/var/log/caddy 
    environment:
      - TZ=Asia/Bangkok # ตั้งค่า Timezone (แนะนำ)

networks:
  app_net:
    external: true
    
volumes:
  caddy_data:
    external: true
  caddy_config:
    external: true
</code></pre>
        
    </li>
    <li>คลิก <strong>Deploy the stack</strong></li>
</ol>
<p>
    เมื่อ Caddy เริ่มทำงานครั้งแรก มันจะสร้าง Internal Root CA ของตัวเองขึ้นมาและเก็บไว้ใน Volume <code>caddy_config</code> จากนั้นจะเริ่มออก Certificate ให้กับโดเมนต่างๆ ตามที่กำหนดใน `Caddyfile`
</p>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 3: ติดตั้ง Root CA ของ Caddy บนเครื่อง Client</h2>
<p>
    เพื่อให้เครื่องต่างๆ เชื่อถือ Certificate ที่ Caddy ออกให้ คุณต้องนำ Root CA ของ Caddy ไปติดตั้งใน Trust Store ของทุกเครื่องที่ต้องเข้าถึง Service ผ่าน HTTPS <strong>"ทุกเครื่อง" ในที่นี้ไม่ได้มีแค่เครื่องเบราว์เซอร์ของผู้ใช้เท่านั้น
    แต่รวมถึงตัว Server เองและ Server อื่นๆ ที่ต้องเรียก API/Pull Image ผ่าน HTTPS นี้ด้วย</strong> หน้านี้จะแยกอธิบายทีละกรณีให้ชัดเจน
    (ตัวอย่างนี้ใช้ IP <code>10.20.30.101</code> เป็น Server ที่รัน Caddy และ <code>192.168.100.102</code> เป็น Production Server อีกเครื่อง
    ตามชื่อ/IP ที่ใช้ตลอดคู่มือนี้ ให้แก้เป็น IP จริงของคุณ)
</p>

<h3>ขั้นตอนที่ 3.1: ดึงไฟล์ Root CA (`root.crt`) ออกมาจาก Caddy Container</h3>
<p>รันคำสั่งนี้บน Server ที่รัน Caddy Container (เช่น <code>10.20.30.101</code>):</p>
<pre><code>docker cp caddy:/data/caddy/pki/authorities/local/root.crt /opt/caddy/caddy-root.crt
sudo chmod 644 /opt/caddy/caddy-root.crt</code></pre>
<p>
    <code>docker cp</code> คัดลอกไฟล์ Root CA ที่ Caddy สร้างขึ้นเองตอนเริ่มทำงานครั้งแรก (เก็บอยู่ภายใน Container ที่ Path <code>/data/caddy/pki/authorities/local/root.crt</code>) ออกมาไว้บน Host ที่
    <code>/opt/caddy/caddy-root.crt</code> จากนั้น <code>chmod 644</code> เปิดสิทธิ์ให้อ่านได้จากทุก User เพราะขั้นตอนถัดไปจะใช้ <code>scp</code> ดึงไฟล์นี้ออกไปยังเครื่องอื่น ถ้าไม่เปิดสิทธิ์อ่านไว้ก่อนจะดึงไม่ได้
</p>

<h3>ขั้นตอนที่ 3.2: ติดตั้งบนเครื่อง Client ที่เป็น Windows (เช่น เครื่องของนักพัฒนา)</h3>
<ol>
    <li>
        <strong>คัดลอกไฟล์มาที่เครื่อง Windows:</strong><br>
        เปิด PowerShell บนเครื่อง Windows แล้วรันคำสั่งนี้เพื่อดึงไฟล์จาก Caddy Server มาไว้ที่เครื่องของคุณ (ต้องตั้งค่า SSH Key ไว้ก่อนตามบท
        <a href="#" onclick="document.querySelector('a[data-page=\'ubuntu-setup\']').click();">การเตรียม Ubuntu Server</a> ไม่งั้นจะถูกถามรหัสผ่าน):
        <pre><code class="language-powershell">scp devops@10.20.30.101:/opt/caddy/caddy-root.crt C:/Users/Public</code></pre>
        คำสั่งนี้ดึงไฟล์จาก Caddy Server (User <code>devops</code>, IP <code>10.20.30.101</code>) มาวางไว้ที่ <code>C:\Users\Public\caddy-root.crt</code> บนเครื่อง Windows
        (จะใช้วิธีอื่น เช่น USB Drive ก็ได้ ถ้าไม่สะดวกใช้ scp)
    </li>
    <li>
        <strong>ติดตั้ง Certificate บน Windows (ด้วย PowerShell):</strong><br>
        - คลิกขวาที่ปุ่ม Start > เลือก **"Windows PowerShell (Admin)"** หรือ **"Terminal (Admin)"**<br>
        - รันคำสั่ง `Import-Certificate` โดยระบุตำแหน่งไฟล์และ Store ที่ต้องการติดตั้ง:
        <pre><code class="language-powershell">Import-Certificate -FilePath "C:\Users\Public\caddy-root.crt" -CertStoreLocation Cert:\LocalMachine\Root</code></pre>
        <p>คำสั่งนี้ติดตั้งไฟล์เข้าสู่ "Trusted Root Certification Authorities" Store ของ Windows ทำให้ Browser บนเครื่องนี้เชื่อถือ HTTPS ที่ Caddy ออกให้ทันที คุณจะเห็นผลลัพธ์แสดง Thumbprint ของ Certificate ที่ถูกติดตั้ง</p>
        <div class="image-container">
            <img src="images/08-01-import-root-ca.png" alt="PowerShell Import-Certificate command result" class="content-image">
            <p>ตัวอย่างผลลัพธ์หลังจากรันคำสั่ง Import-Certificate</p>
        </div>
    </li>
    <li>
        <strong>(ทางเลือก) ตรวจสอบการติดตั้ง:</strong><br>
        คุณสามารถใช้คำสั่ง PowerShell เหล่านี้เพื่อตรวจสอบว่า Certificate ถูกติดตั้งใน Store ที่ถูกต้องหรือไม่:
        <pre><code class="language-powershell"># วิธีที่ 1: ใช้ certutil (อาจจะต้องติดตั้ง)
certutil -store -enterprise Root | findstr /I "Caddy Local Authority"

# วิธีที่ 2: ใช้ Get-ChildItem (แนะนำ)
Get-ChildItem Cert:\LocalMachine\Root | Where-Object Subject -like "*Caddy Local Authority*" | Select-Object Subject, Thumbprint, NotAfter</code></pre>
        <div class="image-container">
            <img src="images/08-02-check-root-ca.png" alt="PowerShell Get-ChildItem command result for Caddy CA" class="content-image">
            <p>ตัวอย่างการตรวจสอบ Certificate ด้วย Get-ChildItem</p>
        </div>
    </li>
    <li>
        <strong>รีสตาร์ทเบราว์เซอร์:</strong><br>
        ปิดเบราว์เซอร์ทั้งหมดแล้วเปิดใหม่ เพื่อให้เบราว์เซอร์อ่านค่า Certificate ที่เพิ่งติดตั้งเข้าไป
    </li>
</ol>

<h3>ขั้นตอนที่ 3.3: ติดตั้งบนเครื่อง Server ที่รัน Caddy เอง (Linux)</h3>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
    <strong>ทำไมต้องทำขั้นตอนนี้ด้วย:</strong> ขั้นตอนที่ 3.2 ทำให้แค่ <strong>Browser</strong> บนเครื่อง Windows เชื่อถือ Certificate แต่โปรแกรมที่รันบน Command Line ของตัว Caddy Server เอง (เช่น
    <code>curl</code>, <code>git</code>, <code>docker</code>) จะยังไม่เชื่อถือ Certificate นี้ เพราะ Linux แยก Trust Store ของระบบปฏิบัติการออกจาก Trust Store ของ Browser จึงต้องติดตั้งซ้ำอีกชุดสำหรับระบบปฏิบัติการ
    ถ้าคุณมี Service บน Server นี้ที่ต้องเรียกกลับมาหา Service อื่นผ่าน <code>https://...vecskill.ovec</code> (เช่น GitLab Runner เรียก Registry) ต้องทำขั้นตอนนี้
</p>
<p>รันคำสั่งนี้บน Caddy Server เอง (<code>10.20.30.101</code>):</p>
<pre><code>sudo cp /opt/caddy/caddy-root.crt /usr/local/share/ca-certificates/caddy-root.crt
sudo update-ca-certificates</code></pre>
<p>
    <code>cp</code> ก๊อปไฟล์เข้าโฟลเดอร์มาตรฐานของ Ubuntu/Debian สำหรับเก็บ Certificate เพิ่มเติม (<code>/usr/local/share/ca-certificates/</code> — ไฟล์ในนี้ต้องนามสกุล <code>.crt</code> เท่านั้นถึงจะถูกตรวจพบ)
    จากนั้น <code>update-ca-certificates</code> จะสแกนโฟลเดอร์นี้และรวม Certificate ใหม่เข้ากับ Trust Bundle ของระบบ (<code>/etc/ssl/certs/ca-certificates.crt</code>) โดยอัตโนมัติ
</p>

<h3>ขั้นตอนที่ 3.4: ติดตั้งบนเครื่อง Server อื่นๆ ที่ต้องเชื่อถือ Certificate นี้ด้วย (เช่น Production Server)</h3>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
    <strong>ทำไมต้องทำขั้นตอนนี้ด้วย:</strong> ถ้าคุณทำตามวิธี "Push ผ่าน HTTPS ที่เชื่อถือได้" ในบท
    <a href="#" onclick="document.querySelector('a[data-page=\'registry\']').click();">การติดตั้ง Registry & UI</a> เครื่อง Production Server ที่ GitLab Runner SSH เข้าไป Deploy ตามบท
    <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a> ก็ต้องเชื่อถือ Certificate ของ Caddy ด้วยเช่นกัน ไม่งั้น <code>docker compose pull</code>
    บนเครื่องนั้นจะล้มเหลวด้วย TLS Error เพราะไม่รู้จัก Root CA นี้
</p>
<p>ทำตามนี้บน Production Server (ตัวอย่างใช้ IP <code>192.168.100.102</code> ตามบท CI/CD):</p>
<pre><code># 1. ดึงไฟล์ Root CA จาก Caddy Server มาไว้ที่ Production Server
scp devops@10.20.30.101:/opt/caddy/caddy-root.crt /opt/caddy-root.crt

# 2. ติดตั้งเข้า Trust Store ของระบบ (เหมือนขั้นตอนที่ 3.3)
sudo cp /opt/caddy-root.crt /usr/local/share/ca-certificates/caddy-root.crt
sudo update-ca-certificates</code></pre>
<p style="font-size: 0.9em; color: #666">
    <em>หมายเหตุ: บน Production Server ไม่จำเป็นต้องสร้างโฟลเดอร์ <code>/opt/caddy/</code> เพราะเครื่องนี้ไม่ได้รัน Caddy เก็บไฟล์ไว้ที่ <code>/opt/caddy-root.crt</code> ตรงๆ ก็พอ
    ทำขั้นตอนนี้ซ้ำกับทุกเครื่องที่ต้อง Pull Image ผ่าน Registry ที่อยู่หลัง Caddy</em>
</p>

<hr style="margin: 40px 0;">

<h2>ขั้นตอนที่ 4: ทดสอบการเข้าใช้งาน</h2>
<p>
    หลังจากติดตั้ง Root CA บนเครื่อง Client และรีสตาร์ทเบราว์เซอร์แล้ว ลองเข้าถึง Service ภายในของคุณผ่าน HTTPS ได้เลย เช่น:
</p>
<ul>
    <li><code>https://gitlab.vecskill.ovec</code></li>
    <li><code>https://portainer.vecskill.ovec</code></li>
    <li><code>https://dns.vecskill.ovec</code></li>
</ul>
<p>
    คุณควรจะสามารถเข้าถึงได้โดยไม่มีคำเตือนเรื่องความปลอดภัยจากเบราว์เซอร์อีกต่อไป และจะเห็นไอคอนแม่กุญแจสีเขียว (หรือสีเทา) แสดงว่าการเชื่อมต่อปลอดภัยและน่าเชื่อถือแล้ว! 🔒
    <div class="image-container">
            <img src="images/08-04-gitlab-ssl.png" alt="PowerShell Get-ChildItem command result for Caddy CA" class="content-image">
            <p>ตัวอย่างการการเข้าใช้งาน <code>https://gitlab.vecskill.ovec</code> *** ต้องมีการปรับแต่ง Stack ของ gitlab-stack</p>
        </div>
    ตัวอย่างการปรับแต่ง Stack ของ gitlab-stack เพื่อให้ทำงานร่วมกับ Caddy Internal CA:
    <pre><code>services:
  gitlab:
    image: 'gitlab/gitlab-ce:latest'
    container_name: gitlab
    restart: always
    hostname: 'gitlab.vecskill.ovec'
    environment:
      GITLAB_OMNIBUS_CONFIG: |
        external_url 'https://gitlab.vecskill.ovec'
        nginx['listen_port'] = 80
        nginx['listen_https'] = false
        letsencrypt['enable'] = false
        gitlab_rails['gitlab_shell_ssh_port'] = 2222
        # ปิด Prometheus monitoring ของ GitLab ไว้ (ไม่จำเป็นสำหรับการแข่งขัน ช่วยประหยัดทรัพยากร)
        # หากต้องการเปิดใช้งาน ให้เปลี่ยนเป็น true และกำหนด shm_size ของ service gitlab ไว้ที่ประมาณ 512m - 2g ด้านล่าง
        prometheus_monitoring['enable'] = false
        # เก็บ IP จริงเมื่อมี reverse proxy
        nginx['real_ip_header'] = 'X-Forwarded-For'
        nginx['real_ip_recursive'] = 'on'
        nginx['real_ip_trusted_addresses'] = ['172.16.0.0/12','192.168.0.0/16','10.0.0.0/8']
    ports:
      - '2222:22'
    shm_size: "1g"
    volumes:
      - 'gitlab_config:/etc/gitlab'
      - 'gitlab_logs:/var/log/gitlab'
      - 'gitlab_data:/var/opt/gitlab'
    healthcheck:
      test: ["CMD-SHELL", "curl -fsS http://localhost/-/health || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 10
    networks:
      - app_net

networks:
  app_net:
    external: true

volumes:
  gitlab_config:
    external: true
  gitlab_logs:
    external: true
  gitlab_data:
    external: true</code></pre>
</p>