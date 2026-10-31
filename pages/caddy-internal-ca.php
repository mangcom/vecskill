<h1>การตั้งค่า Caddy เป็น Internal CA (สำหรับ HTTPS ภายใน)</h1>
<p>
    ในบทก่อนๆ เราได้เรียนรู้วิธีใช้ Caddy หรือ Nginx Proxy Manager เพื่อทำ Reverse Proxy และขอ SSL Certificate จาก Let's Encrypt สำหรับโดเมนที่เข้าถึงได้จากอินเทอร์เน็ต (Public Domain) แต่วิธีนั้นอาจไม่เหมาะสำหรับ Service ที่เราต้องการให้เข้าถึงได้เฉพาะภายในเครือข่ายของเราเท่านั้น
</p>
<p>
    Caddy มีฟีเจอร์ที่ทรงพลังคือความสามารถในการทำหน้าที่เป็น **Certificate Authority (CA) ภายใน** ของตัวเองได้ มันสามารถออก SSL Certificate ที่ถูกต้องและน่าเชื่อถือ *สำหรับใช้งานภายในเครือข่ายของคุณ* ได้โดยอัตโนมัติ ทำให้คุณสามารถเข้าถึง Service ต่างๆ เช่น <code>https://gitlab.vecskill.ovec</code> หรือ <code>https://portainer.vecskill.ovec</code> ด้วย HTTPS โดยไม่มีคำเตือนเรื่องความปลอดภัยจากเบราว์เซอร์เลย!
</p>
<p>
    วิธีนี้เหมาะอย่างยิ่งสำหรับ Homelab หรือเครือข่ายภายในองค์กรที่ไม่ต้องการเปิดเผย Service สู่โลกภายนอก
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
	# สั่งให้ Caddy ใช้ CA ภายในสำหรับทุกโดเมนในไฟล์นี้
	cert_issuer internal
	# auto_https on  # เปิดใช้งาน HTTPS อัตโนมัติ (เป็นค่าเริ่มต้นอยู่แล้ว)
}

# ---------- Technitium DNS (Web Admin) ----------
dns.vecskill.ovec {
	tls internal # บอกให้ใช้ Certificate จาก Internal CA
	encode zstd gzip
	reverse_proxy http://technitium-dns:5380
}

# ---------- Portainer ----------
# (ตัวอย่างนี้สำหรับ Portainer ที่รันบน HTTPS Port 9443)
portainer.vecskill.ovec {
	tls internal
	encode zstd gzip
	reverse_proxy https://portainer:9443 {
		transport http {
			tls_insecure_skip_verify # จำเป็นเพราะ Certificate ของ Portainer เป็น Self-signed
		}
	}
}

# ---------- Docker Registry (Distribution v2) ----------
registry.vecskill.ovec {
	tls internal
	encode zstd gzip
	header Docker-Distribution-Api-Version "registry/2.0" # Header เฉพาะของ Registry
	reverse_proxy http://registry:5000 {
		header_up X-Forwarded-Proto {scheme}
	}
}

# ---------- Registry UI ----------
registry-ui.vecskill.ovec {
	tls internal
	encode zstd gzip
	reverse_proxy http://registry-ui:80 {
		header_up X-Forwarded-Proto {scheme}
	}
}

# ---------- NPM (Verdaccio - สมมติว่ามี Container ชื่อ verdaccio) ----------
npm.vecskill.ovec {
	tls internal
	encode zstd gzip
	header Strict-Transport-Security "max-age=31536000;"
	log {
		output file /var/log/caddy/npm_access.log
	}
	reverse_proxy http://verdaccio:4873
}

# ---------- PyPI (devpi - สมมติว่ามี Container ชื่อ devpi) ----------
pypi.vecskill.ovec {
	tls internal
	encode zstd gzip
	reverse_proxy http://devpi:3141 {
		header_up X-Forwarded-Proto {scheme}
		header_up X-Outside-Url {scheme}://{host} # Header เฉพาะของ devpi
	}
}

# ---------- APT cacher (สมมติว่ามี Container ชื่อ aptcacher) ----------
apt.vecskill.ovec {
	tls internal
	encode zstd gzip
	reverse_proxy http://aptcacher:3142
}

# ---------- GitLab ----------
gitlab.vecskill.ovec {
	tls internal
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
    เพื่อให้เบราว์เซอร์บนเครื่องคอมพิวเตอร์ของคุณเชื่อถือ Certificate ที่ Caddy ออกให้ คุณต้องนำ Root CA ของ Caddy ไปติดตั้งใน "Trusted Root Certification Authorities" store ของระบบปฏิบัติการที่คุณใช้งาน **ขั้นตอนนี้ต้องทำบนเครื่อง Client ทุกเครื่องที่ต้องการเข้าถึง Service ผ่าน HTTPS**
</p>
<ol>
    <li>
        <strong>ดึงไฟล์ Root CA (`root.crt`) ออกมาจาก Caddy Container:</strong><br>
        รันคำสั่งนี้บน Server ที่รัน Caddy Container
        <pre><code>docker cp caddy:/data/caddy/pki/authorities/local/root.crt /opt/caddy/caddy-root.crt
          sudo chmod 644 /opt/caddy/caddy-root.crt</code></pre>
        ตอนนี้คุณจะได้ไฟล์ Certificate ชื่อ `caddy-root.crt` อยู่ใน `/opt/caddy/` บน Server ของคุณ
    </li>
    <li>
        <strong>คัดลอกไฟล์ `caddy-root.crt` ไปยังเครื่อง Client:</strong><br>
        ใช้วิธีใดก็ได้ที่คุณสะดวก (เช่น SCP, USB Drive) เพื่อนำไฟล์นี้ไปไว้บนเครื่องคอมพิวเตอร์ของคุณ (ตัวอย่าง: วางไว้ที่ <code>C:\Users\Public\caddy-root.crt</code> บน Windows)
    </li>
    <li>
        <strong>ติดตั้ง Certificate บน Windows (ด้วย PowerShell):</strong><br>
        - คลิกขวาที่ปุ่ม Start > เลือก **"Windows PowerShell (Admin)"** หรือ **"Terminal (Admin)"**<br>
        - รันคำสั่ง `Import-Certificate` โดยระบุตำแหน่งไฟล์และ Store ที่ต้องการติดตั้ง:
        <pre><code class="language-powershell">Import-Certificate -FilePath "C:\Users\Public\caddy-root.crt" -CertStoreLocation Cert:\LocalMachine\Root</code></pre>
        <p>คุณจะเห็นผลลัพธ์แสดง Thumbprint ของ Certificate ที่ถูกติดตั้ง</p>
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