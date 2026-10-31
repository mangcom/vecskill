<h1>การติดตั้งและใช้งาน Trivy (Vulnerability Scanner)</h1>
<p>
  Trivy คือเครื่องมือสแกนหาช่องโหว่ (Vulnerability) แบบ All-in-One จาก Aqua Security ใช้สแกนได้หลายอย่าง: Docker Image, ไฟล์ในโปรเจกต์ (Dependencies เช่น <code>package.json</code>),
  Dockerfile/docker-compose.yaml (Misconfiguration), และ Secret ที่หลุดอยู่ในโค้ด (เช่น API Key ที่ลืมลบ) เหมาะสำหรับใส่ไว้ใน CI/CD Pipeline ก่อน Push Image ขึ้น Registry
  เพื่อเช็คว่า Image ที่ Build มามีช่องโหว่ร้ายแรงหรือไม่
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>⏱ สำคัญมากสำหรับการแข่งขัน (Offline):</strong> Trivy ต้องดาวน์โหลด "ฐานข้อมูลช่องโหว่" (Vulnerability Database) จาก Internet ก่อนจึงจะสแกนได้ถูกต้อง โดยปกติ Database จะถูกดึงมาโดยอัตโนมัติทุกครั้งที่สแกน
  ถ้าอินเทอร์เน็ตถูกตัดไปแล้วและยังไม่เคยดาวน์โหลด Database ไว้ก่อน <strong>Trivy จะสแกนไม่ได้เลย</strong> ต้องทำตามขั้นตอน "ดาวน์โหลด Database ล่วงหน้า" ด้านล่างให้เสร็จ
  <strong>ก่อน</strong>อินเทอร์เน็ตถูกตัด
</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 1: ดาวน์โหลด Database ล่วงหน้า (ทำตอนยังมีอินเทอร์เน็ต)</h2>
<p>สร้าง Volume เก็บ Cache ของ Trivy ไว้ถาวร แล้วสั่งดาวน์โหลด Database ลงมาเก็บไว้ล่วงหน้า:</p>
<pre><code>docker volume create trivy_cache

# ดาวน์โหลดเฉพาะ Database (ไม่สแกนอะไร)
docker run --rm -v trivy_cache:/root/.cache/ aquasec/trivy:latest image --download-db-only</code></pre>
<p>Database ที่โหลดมาจะถูกเก็บไว้ใน Volume <code>trivy_cache</code> และมีอายุใช้งานได้ประมาณ 1-3 วัน (Trivy จะเตือนถ้า Database เก่าเกินไป แต่ยังใช้สแกนต่อได้ด้วยคำสั่งด้านล่าง)</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 2: สแกน Docker Image</h2>
<p>สมมติว่าคุณ Build Image ชื่อ <code>myapp:latest</code> ไว้แล้ว (ดูวิธี Build ได้จากบท CI/CD) ให้สแกนด้วยคำสั่งนี้:</p>
<pre><code># --skip-db-update คือใช้ Database ที่โหลดไว้ล่วงหน้า ไม่ต้องต่อเน็ตไปโหลดใหม่ (สำคัญตอนไม่มีเน็ตแล้ว)
docker run --rm \
  -v trivy_cache:/root/.cache/ \
  -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy:latest image --skip-db-update --skip-java-db-update myapp:latest</code></pre>
<p>ผลลัพธ์จะแสดงรายการช่องโหว่ (CVE) ที่พบในแต่ละ Library/Package ภายใน Image พร้อมระดับความรุนแรง (LOW, MEDIUM, HIGH, CRITICAL)</p>

<h3>2.1 กรองเฉพาะช่องโหว่ระดับร้ายแรง</h3>
<p>Image ส่วนใหญ่มักเจอช่องโหว่ระดับ LOW/MEDIUM จำนวนมากที่ยังไม่มี Patch ให้ ในทางปฏิบัติมักกรองดูเฉพาะที่ร้ายแรงจริงๆ:</p>
<pre><code>docker run --rm -v trivy_cache:/root/.cache/ -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy:latest image --skip-db-update --severity HIGH,CRITICAL myapp:latest</code></pre>

<h3>2.2 สั่งให้ Fail ถ้าเจอช่องโหว่ร้ายแรง (เหมาะสำหรับ CI/CD)</h3>
<pre><code>docker run --rm -v trivy_cache:/root/.cache/ -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy:latest image --skip-db-update --severity CRITICAL --exit-code 1 myapp:latest</code></pre>
<p><code>--exit-code 1</code> จะทำให้คำสั่งคืนค่า Error ถ้าเจอช่องโหว่ระดับที่กำหนด ซึ่งจะทำให้ GitLab CI Pipeline หยุดทำงานที่ Stage นี้โดยอัตโนมัติ</p>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 3: สแกนโปรเจกต์ (Filesystem) และ Dockerfile</h2>
<p>นอกจากสแกน Image แล้ว ยังสแกนโฟลเดอร์โปรเจกต์โดยตรงได้ เพื่อเช็ค Dependencies ใน <code>package.json</code>/<code>package-lock.json</code> และหา Secret ที่หลุดอยู่ในโค้ด:</p>
<pre><code># เข้าไปที่โฟลเดอร์โปรเจกต์ก่อน แล้วรัน
docker run --rm -v trivy_cache:/root/.cache/ -v $(pwd):/myproject \
  aquasec/trivy:latest fs --skip-db-update /myproject</code></pre>
<p>สแกน Dockerfile/docker-compose.yaml หา Misconfiguration (เช่น รันเป็น root, เปิด Port ไม่จำเป็น):</p>
<pre><code>docker run --rm -v $(pwd):/myproject aquasec/trivy:latest config /myproject</code></pre>

<hr style="margin: 40px 0" />

<h2>ส่วนที่ 4: การใส่ไว้ใน GitLab CI/CD Pipeline</h2>
<p>เพิ่ม Stage สแกน Image ก่อน Push ขึ้น Registry ในไฟล์ <code>.gitlab-ci.yml</code>:</p>
<pre><code class="language-yaml">scan_image:
  stage: test
  image: aquasec/trivy:latest
  tags:
    - docker
  script:
    # --skip-db-update เพราะ Runner อาจไม่มีเน็ตตอนรันจริง ต้อง pre-load cache ไว้ก่อนแล้ว
    - trivy image --skip-db-update --severity HIGH,CRITICAL --exit-code 1 $IMAGE_NAME
  allow_failure: true # ปรับเป็น false เมื่อทีมพร้อมให้ Pipeline หยุดจริงเมื่อเจอช่องโหว่ร้ายแรง
</code></pre>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: ถ้า GitLab Runner ของคุณสามารถต่ออินเทอร์เน็ตได้ตลอด สามารถเอา <code>--skip-db-update</code> ออกได้เพื่อให้ Database อัปเดตใหม่ทุกครั้งที่สแกน (แม่นยำกว่า) แต่ถ้าใช้ในเครือข่ายปิดของการแข่งขัน
  ต้องทำตามขั้นตอนดาวน์โหลด Database ล่วงหน้าในส่วนที่ 1 ก่อนเสมอ</em>
</p>
