<h1>การติดตั้งและใช้งาน OWASP ZAP (Zed Attack Proxy)</h1>
<p>
  OWASP ZAP คือเครื่องมือทดสอบความปลอดภัยเว็บแอปพลิเคชันแบบ DAST (Dynamic Application Security Testing) ที่ได้รับความนิยมสูงและเป็น Open Source ฟรี 100% ทำหน้าที่ยิง Request
  เข้าไปทดสอบเว็บแอปของเรา (เหมือนแฮกเกอร์จริงๆ) แล้วรายงานช่องโหว่ที่พบ เช่น SQL Injection, XSS, Security Header ที่ขาดหายไป
</p>
<p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
  <strong>⚠️ ใช้เฉพาะกับระบบของตัวเอง:</strong> ให้สแกนเฉพาะเว็บแอปที่คุณพัฒนาเองหรือได้รับอนุญาตให้ทดสอบเท่านั้น (เช่น ระบบที่ทีมพัฒนาขึ้นในการแข่งขันนี้) <strong>ห้ามใช้สแกนเว็บไซต์ของผู้อื่นโดยไม่ได้รับอนุญาตเด็ดขาด</strong>
  เพราะผิดกฎหมาย
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>เรื่องการทำงานแบบ Offline:</strong> ตัวสแกนเองทำงานออฟไลน์ได้สมบูรณ์ (แค่ยิงไปที่เว็บแอปในเครือข่ายภายในของคุณเอง ไม่ต้องพึ่งอินเทอร์เน็ต) แต่ต้อง <strong>ดาวน์โหลด Docker Image มาเก็บไว้ก่อน</strong>
  ตอนที่ยังมีอินเทอร์เน็ต เพราะ Image มีขนาดค่อนข้างใหญ่และมี Ruleset ตรวจสอบช่องโหว่ฝังอยู่ในตัวแล้ว
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: เตรียม Docker Image</h2>
<p>ดึง Image มาเก็บไว้ล่วงหน้า (ทำตอนที่ยังมีอินเทอร์เน็ต):</p>
<pre><code>docker pull zaproxy/zap-stable</code></pre>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: Image ชื่อเก่า <code>owasp/zap2docker-stable</code> เลิกอัปเดตแล้ว ให้ใช้ <code>zaproxy/zap-stable</code> (Docker Hub) หรือ <code>ghcr.io/zaproxy/zaproxy:stable</code> (GitHub Container Registry)
  แทนเสมอ</em>
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: Baseline Scan (สแกนแบบเร็ว ปลอดภัย แนะนำให้เริ่มจากตัวนี้)</h2>
<p>
  Baseline Scan เป็นการสแกนแบบ <strong>Passive</strong> เท่านั้น (ดักฟังและวิเคราะห์ Traffic ไม่ได้ยิงโจมตีจริง) ใช้เวลาไม่นานและปลอดภัยที่จะรันกับระบบที่ใช้งานจริงได้ เหมาะสำหรับรันเป็นประจำ
  หรือใส่ไว้ใน CI/CD Pipeline
</p>
<p>
  สมมติว่าเว็บแอปของคุณรันอยู่ใน Container ชื่อ <code>myapp</code> บน Network <code>app_net</code> (ดูวิธีสร้าง Network นี้ได้จากบทการติดตั้ง Registry) ให้รันคำสั่งนี้:
</p>
<pre><code>docker run --rm --network app_net \
  -v $(pwd)/zap-reports:/zap/wrk/:rw \
  zaproxy/zap-stable zap-baseline.py \
  -t http://myapp:3000 \
  -r baseline-report.html</code></pre>
<ul>
  <li><code>-t http://myapp:3000</code>: URL เป้าหมายที่จะสแกน (แก้ชื่อ Container/Port ให้ตรงกับแอปของคุณ)</li>
  <li><code>-r baseline-report.html</code>: ชื่อไฟล์รายงานผล (HTML) ที่จะถูกสร้างในโฟลเดอร์ <code>zap-reports</code> บนเครื่อง Host</li>
  <li><code>--rm</code>: ลบ Container ทิ้งหลังสแกนเสร็จ (Image ไม่ได้ถูกลบ ครั้งต่อไปรันได้ทันทีไม่ต้องโหลดใหม่)</li>
</ul>
<p>เปิดไฟล์ <code>zap-reports/baseline-report.html</code> ด้วยเบราว์เซอร์เพื่อดูผลสแกน จะเห็นรายการ "WARN" และ "FAIL" พร้อมคำอธิบายช่องโหว่แต่ละประเภท</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: Full Scan (สแกนแบบเจาะลึก - Active Scan)</h2>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ข้อควรระวัง:</strong> Full Scan จะ <strong>ยิงโจมตีจริง</strong> (เช่น พยายามฉีด SQL Injection จริงๆ) เพื่อทดสอบว่าระบบป้องกันได้หรือไม่ อาจทำให้ข้อมูลในฐานข้อมูลทดสอบเสียหายหรือระบบทำงานหนักขึ้นมาก
  <strong>ห้ามรันกับระบบที่ใช้งานจริง (Production) ที่มีข้อมูลสำคัญเด็ดขาด</strong> ให้รันกับ Environment สำหรับทดสอบเท่านั้น
</p>
<pre><code>docker run --rm --network app_net \
  -v $(pwd)/zap-reports:/zap/wrk/:rw \
  zaproxy/zap-stable zap-full-scan.py \
  -t http://myapp:3000 \
  -r full-report.html</code></pre>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: รันแบบ Daemon เพื่อใช้งานผ่าน Web UI/API</h2>
<p>หากต้องการเปิด ZAP ค้างไว้เพื่อควบคุมผ่าน API หรือเชื่อมกับเครื่องมืออื่น:</p>
<pre><code>docker run -u zap -p 8090:8090 -i zaproxy/zap-stable zap.sh -daemon \
  -host 0.0.0.0 -port 8090 \
  -config api.addrs.addr.name=.* \
  -config api.addrs.addr.regex=true \
  -config api.key=ใส่คีย์ลับของคุณที่นี่</code></pre>
<p>จากนั้นเรียกใช้ผ่าน API ได้ที่ <code>http://[IP_SERVER]:8090</code> โดยแนบ <code>apikey=</code> ตามที่ตั้งไว้ในทุก Request</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 5: การใส่ไว้ใน GitLab CI/CD Pipeline</h2>
<p>
  เพิ่ม Stage สำหรับ Security Scan ในไฟล์ <code>.gitlab-ci.yml</code> (ดูพื้นฐานการเขียน Pipeline เพิ่มเติมได้ที่บท
  <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a>):
</p>
<pre><code class="language-yaml">security_scan:
  stage: test
  image: zaproxy/zap-stable
  tags:
    - docker
  script:
    - mkdir -p /zap/wrk
    - zap-baseline.py -t http://myapp:3000 -r zap-report.html -I
  artifacts:
    paths:
      - /zap/wrk/zap-report.html
    when: always
  allow_failure: true # ไม่บล็อก Pipeline ถ้าเจอ Warning (ปรับตามนโยบายของทีม)
</code></pre>
<p><code>-I</code> คือสั่งให้ Exit Code เป็น 0 เสมอแม้เจอ Warning (ใช้ตอนเริ่มต้นใช้งาน ภายหลังอยากให้ Pipeline Fail เมื่อเจอช่องโหว่ร้ายแรงให้เอาออก)</p>
