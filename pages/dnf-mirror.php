<h1>การทำ DNF Mirror (Local Repository สำหรับ CentOS/Rocky)</h1>
<p>
  คู่มือนี้จะอธิบายวิธีการสร้าง Local Mirror สำหรับ <strong>Rocky Linux 9</strong> และ <strong>CentOS Stream 9</strong> บนเครื่อง Server ที่ใช้ OS เป็น <strong>Ubuntu</strong> (IP: 10.20.30.101)
  โดยใช้ <strong>Apache2</strong> (ตัวเดียวกับที่ใช้ทำ APT Mirror) เพื่อให้บริการไฟล์แบบ Offline 100%
</p>
<p style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px">
  <strong>Concept:</strong> เราจะใช้เครื่องมือ <code>dnf</code> บน Ubuntu เพื่อดาวน์โหลดแพ็กเกจ (.rpm) มาเก็บไว้ใน Web Root ของ Apache2 (<code>/var/www/html</code>)
  เพื่อให้เครื่องลูกข่ายดึงไปติดตั้งได้ทันที
</p>

<hr style="margin: 40px 0" />

<h2>Phase 1: เตรียมเครื่อง Server (Host: Ubuntu)</h2>

<h3>1.1 ติดตั้ง Package ที่จำเป็น</h3>
<p>ติดตั้ง <code>dnf</code> และ <code>createrepo-c</code> (ส่วน Apache2 น่าจะติดตั้งไว้แล้วจากการทำ APT Mirror):</p>
<pre><code>sudo apt update
sudo apt install -y apache2 dnf dnf-plugins-core createrepo-c</code></pre>

<h3>1.2 สร้างโฟลเดอร์ Config สำหรับ DNF</h3>
<pre><code>sudo mkdir -p /etc/dnf/repos.d</code></pre>

<h3>1.3 สร้างไฟล์ Config ของ Rocky Linux 9</h3>
<p>สร้างไฟล์ <code>/etc/dnf/repos.d/rocky-mirror.repo</code>:</p>
<pre><code>sudo nano /etc/dnf/repos.d/rocky-mirror.repo</code></pre>
<p>วางเนื้อหานี้ลงไป:</p>
<pre><code class="language-ini">[rocky-baseos]
name=Rocky Linux 9 - BaseOS
baseurl=https://dl.rockylinux.org/pub/rocky/9/BaseOS/x86_64/os/
gpgcheck=0

[rocky-appstream]
name=Rocky Linux 9 - AppStream
baseurl=https://dl.rockylinux.org/pub/rocky/9/AppStream/x86_64/os/
gpgcheck=0

[rocky-extras]
name=Rocky Linux 9 - Extras
baseurl=https://dl.rockylinux.org/pub/rocky/9/extras/x86_64/os/
gpgcheck=0
</code></pre>

<h3>1.4 สร้างไฟล์ Config ของ CentOS Stream 9</h3>
<p>สร้างไฟล์ <code>/etc/dnf/repos.d/centos-mirror.repo</code>:</p>
<pre><code>sudo nano /etc/dnf/repos.d/centos-mirror.repo</code></pre>
<p>วางเนื้อหานี้ลงไป:</p>
<pre><code class="language-ini">[centos-baseos]
name=CentOS Stream 9 - BaseOS
baseurl=https://mirror.stream.centos.org/9-stream/BaseOS/x86_64/os/
gpgcheck=0

[centos-appstream]
name=CentOS Stream 9 - AppStream
baseurl=https://mirror.stream.centos.org/9-stream/AppStream/x86_64/os/
gpgcheck=0

[centos-extras]
name=CentOS Stream 9 - Extras
baseurl=https://mirror.stream.centos.org/9-stream/extras/x86_64/os/
gpgcheck=0
</code></pre>

<hr style="margin: 40px 0" />

<h2>Phase 2: สร้าง Script ดาวน์โหลด (Sync Script)</h2>
<p>เราจะสร้าง Shell Script เพื่อดาวน์โหลดไฟล์ไปเก็บไว้ที่ <code>/var/www/html</code> โดยตรง ซึ่ง Apache จะมองเห็นทันที</p>

<h3>2.1 สร้างไฟล์ Script</h3>
<pre><code>sudo nano /usr/local/bin/sync-rpm-mirror.sh</code></pre>

<h3>2.2 ใส่โค้ด Script</h3>
<pre><code class="language-bash">#!/bin/bash

# --- Config ---
# ใช้ Path เดียวกับ Web Root ของ Apache2
WEB_ROOT="/var/www/html"
ROCKY_DIR="$WEB_ROOT/rocky"
CENTOS_DIR="$WEB_ROOT/centos"

# --- 1. Sync Rocky Linux 9 ---
echo "========================================"
echo "Starting Rocky Linux 9 Sync..."
echo "========================================"
mkdir -p $ROCKY_DIR

# ใช้ --releasever=9 เพื่อระบุเวอร์ชัน
dnf reposync -c /etc/dnf/repos.d/rocky-mirror.repo --repo=rocky-baseos --releasever=9 --download-metadata --newest-only --delete -p $ROCKY_DIR
dnf reposync -c /etc/dnf/repos.d/rocky-mirror.repo --repo=rocky-appstream --releasever=9 --download-metadata --newest-only --delete -p $ROCKY_DIR
dnf reposync -c /etc/dnf/repos.d/rocky-mirror.repo --repo=rocky-extras --releasever=9 --download-metadata --newest-only --delete -p $ROCKY_DIR

# --- 2. Sync CentOS Stream 9 ---
echo "========================================"
echo "Starting CentOS Stream 9 Sync..."
echo "========================================"
mkdir -p $CENTOS_DIR

# ใช้ --releasever=9-stream สำหรับ CentOS Stream
dnf reposync -c /etc/dnf/repos.d/centos-mirror.repo --repo=centos-baseos --releasever=9-stream --download-metadata --newest-only --delete -p $CENTOS_DIR
dnf reposync -c /etc/dnf/repos.d/centos-mirror.repo --repo=centos-appstream --releasever=9-stream --download-metadata --newest-only --delete -p $CENTOS_DIR
dnf reposync -c /etc/dnf/repos.d/centos-mirror.repo --repo=centos-extras --releasever=9-stream --download-metadata --newest-only --delete -p $CENTOS_DIR

# --- 3. Fix Permissions ---
echo "Fixing Permissions for Apache2..."
# เปลี่ยน Owner เป็น www-data (User ของ Apache บน Ubuntu)
chown -R www-data:www-data $WEB_ROOT
chmod -R 755 $WEB_ROOT

echo "All Sync Complete!"
</code></pre>

<h3>2.3 สั่งรัน Script</h3>
<p><strong>คำเตือน:</strong> การดาวน์โหลดครั้งแรกจะมีขนาดใหญ่มาก (หลายสิบ GB) โปรดตรวจสอบพื้นที่ว่างใน <code>/var/www/html</code> ก่อน</p>
<pre><code>sudo chmod +x /usr/local/bin/sync-rpm-mirror.sh
sudo /usr/local/bin/sync-rpm-mirror.sh</code></pre>

<hr style="margin: 40px 0" />

<h2>Phase 3: การตั้งค่าเครื่อง Client (Rocky / CentOS)</h2>
<p>เมื่อดาวน์โหลดเสร็จแล้ว ให้ไปที่เครื่องลูกข่ายเพื่อชี้ Repo มาที่ Server <code>10.20.30.101</code></p>

<h3>3.1 สำหรับเครื่อง Client: Rocky Linux 9</h3>
<p>1. ย้ายไฟล์ repo เก่าไปเก็บไว้ก่อน (Disable ของเดิม):</p>
<pre><code>sudo mkdir /etc/yum.repos.d/backup
sudo mv /etc/yum.repos.d/*.repo /etc/yum.repos.d/backup/</code></pre>
<p>2. สร้างไฟล์ใหม่ <code>/etc/yum.repos.d/local.repo</code>:</p>
<pre><code class="language-ini">[Local-Rocky-BaseOS]
name=Local Rocky BaseOS
baseurl=http://10.20.30.101/rocky/rocky-baseos/
enabled=1
gpgcheck=0

[Local-Rocky-AppStream]
name=Local Rocky AppStream
baseurl=http://10.20.30.101/rocky/rocky-appstream/
enabled=1
gpgcheck=0

[Local-Rocky-Extras]
name=Local Rocky Extras
baseurl=http://10.20.30.101/rocky/rocky-extras/
enabled=1
gpgcheck=0
</code></pre>

<h3>3.2 สำหรับเครื่อง Client: CentOS Stream 9</h3>
<p>1. ย้ายไฟล์ repo เก่าไปเก็บไว้:</p>
<pre><code>sudo mkdir /etc/yum.repos.d/backup
sudo mv /etc/yum.repos.d/*.repo /etc/yum.repos.d/backup/</code></pre>
<p>2. สร้างไฟล์ใหม่ <code>/etc/yum.repos.d/local.repo</code>:</p>
<pre><code class="language-ini">[Local-CentOS-BaseOS]
name=Local CentOS BaseOS
baseurl=http://10.20.30.101/centos/centos-baseos/
enabled=1
gpgcheck=0

[Local-CentOS-AppStream]
name=Local CentOS AppStream
baseurl=http://10.20.30.101/centos/centos-appstream/
enabled=1
gpgcheck=0

[Local-CentOS-Extras]
name=Local CentOS Extras
baseurl=http://10.20.30.101/centos/centos-extras/
enabled=1
gpgcheck=0
</code></pre>

<hr style="margin: 40px 0" />

<h2>Phase 4: ทดสอบ (Verification)</h2>
<p>ที่เครื่อง Client ลองสั่งคำสั่งนี้ ถ้าสำเร็จ มันจะวิ่งไปโหลดข้อมูลจาก IP <code>10.20.30.101</code> ทันที:</p>
<pre><code>sudo dnf clean all
sudo dnf makecache
sudo dnf install git vim htop</code></pre>
