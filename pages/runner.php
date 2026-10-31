<h1>การติดตั้งและใช้งาน GitLab Runner</h1>
<p>
  GitLab Runner คือแอปพลิเคชันที่ทำงานแยกต่างหากจาก GitLab Server ทำหน้าที่รับ "งาน" (Jobs) จาก Pipeline ที่เรากำหนดไว้ในไฟล์
  <code>.gitlab-ci.yml</code> ไปประมวลผล เช่น การคอมไพล์โค้ด, การรัน Unit Test, หรือการสร้าง Docker Image
</p>
<p>ในคู่มือนี้ เราจะติดตั้ง GitLab Runner แบบ Docker Container และตั้งค่าให้มันสามารถใช้คำสั่ง Docker ได้ (Docker Socket Binding) เพื่อใช้ในการ Build Image</p>

<hr style="margin: 40px 0" />

<h2>1. เตรียมข้อมูลสำหรับการลงทะเบียน (Registration Token)</h2>
<p>ก่อนติดตั้ง เราต้องไปเอา "กุญแจ" หรือ Token จาก GitLab Server มาก่อน เพื่อบอกว่า Runner ตัวนี้จะทำงานให้กับใคร</p>
<ol>
  <li>ล็อกอินเข้าสู่ GitLab (<code>https://gitlab.vecskill.ovec</code>) ด้วยสิทธิ์ Administrator (root)</li>
  <li>ไปที่ <strong>Admin Area</strong> (ไอคอนเมนูหรือประแจ) -> <strong>CI/CD</strong> -> <strong>Runners</strong></li>
  <li>คลิกที่ปุ่ม <strong>New instance runner</strong> (หรือมองหา Registration Token ในเวอร์ชันเก่า)</li>
  <li>กำหนด Tags (เช่น <code>docker</code>, <code>linux</code>) และกดสร้าง</li>
  <li><strong>คัดลอก Token ที่ได้เก็บไว้</strong> (เราจะต้องใช้ในขั้นตอนที่ 3)</li>
</ol>
<div class="image-container">
  <p>หน้าจอแสดง Registration Token ใน GitLab Admin Area</p>
</div>

<p>สร้าง Docker Volume สำหรับเก็บ Config ของ Runner (ให้ข้อมูลไม่หายไปเมื่อสร้าง Container ใหม่)</p>
<pre><code>docker volume create gitlab_runner_config</code></pre>

<hr style="margin: 40px 0" />

<h2>2. ติดตั้งผ่าน Portainer Stack</h2>
<p>เราจะติดตั้ง Runner ใน Network เดียวกับ GitLab (`app_net`) และ Mount Volume ที่จำเป็น</p>

<h3>2.1 สร้าง Stack (`runner-stack`)</h3>
<p>ไปที่ Portainer > Stacks > + Add stack และใช้โค้ดนี้:</p>

<pre><code>services:
  gitlab-runner:
    image: gitlab/gitlab-runner:latest
    container_name: gitlab-runner
    restart: always
    networks:
      - app_net
    volumes:
      # เก็บ config.toml ไว้ถาวรใน Volume
      - gitlab_runner_config:/etc/gitlab-runner
      # (สำคัญ) เชื่อมต่อ Docker Socket เพื่อให้ Runner สั่งงาน Docker ได้
      - /var/run/docker.sock:/var/run/docker.sock
    # (ทางเลือก) ใช้เฉพาะกรณี DNS ภายใน (Technitium) ยังไม่พร้อม หรือทดสอบนอก Network เดียวกัน
    # extra_hosts:
    #   - "gitlab.vecskill.ovec:172.16.3.101"
    #   - "registry.vecskill.ovec:172.16.3.101"

networks:
  app_net:
    external: true

volumes:
  gitlab_runner_config:
    external: true
</code></pre>
<p>คลิก <strong>Deploy the stack</strong></p>

<hr style="margin: 40px 0" />

<h2>3. การลงทะเบียน Runner (Register)</h2>
<p>เมื่อ Container รันแล้ว มันยังไม่รู้ว่าจะคุยกับ GitLab ตัวไหน เราต้องเข้าไปตั้งค่ามันผ่าน Command Line</p>
<ol>
  <li>
    <strong>เข้าสู่ Console ของ Runner:</strong><br />
    ใน Portainer คลิกที่ Console ของ `gitlab-runner` หรือใช้ Terminal:
    <pre><code>docker exec -it gitlab-runner bash</code></pre>
  </li>
  <li>
    <strong>รันคำสั่ง Register:</strong>
    <pre><code>gitlab-runner register</code></pre>
  </li>
  <li>
    <strong>ตอบคำถามทีละขั้นตอน:</strong>
    <ul>
      <li>
        <strong>GitLab instance URL:</strong>
        <code>https://gitlab.vecskill.ovec</code> (หรือ <code>http://gitlab</code> ถ้า Runner อยู่ใน Network <code>app_net</code> เดียวกันและยังไม่ได้ทำ HTTPS)
      </li>
      <li><strong>Registration token:</strong> (วาง Token ที่ได้จากขั้นตอนที่ 1)</li>
      <li>
        <strong>Description:</strong> ตั้งชื่อ เช่น
        <code>docker-runner-01</code>
      </li>
      <li><strong>Tags:</strong> <code>docker,build</code> (สำคัญ! ต้องตรงกับที่เรียกใช้ใน Pipeline)</li>
      <li><strong>Maintenance note:</strong> (กด Enter ข้าม)</li>
      <li><strong>Executor:</strong> พิมพ์ว่า <code>docker</code></li>
      <li><strong>Default Docker image:</strong> พิมพ์ว่า <code>docker:24.0.5</code> (หรือ image อื่นที่ต้องการให้เป็นค่าเริ่มต้น)</li>
    </ul>
  </li>
</ol>
<p>หากขึ้นข้อความ <code>Runner registered successfully.</code> แสดงว่าสำเร็จ!</p>

<hr style="margin: 40px 0" />

<h2>4. การปรับแต่ง Config (สำคัญมาก!)</h2>
<p>
  ค่าเริ่มต้นของการ Register ยังไม่เพียงพอสำหรับการ Build Docker Image ด้วยเทคนิค Docker-in-Docker (ตามที่ใช้ในบทการทำ CI/CD) เราต้องแก้ไขไฟล์
  <code>config.toml</code> เพื่อเปิดสิทธิ์ <code>privileged</code> ให้ Runner ก่อน ไม่เช่นนั้น Job ที่เรียกใช้ <code>docker:dind</code> จะ Build/Push Image ไม่ได้
</p>
<ol>
  <li>
    <strong>แก้ไขไฟล์ config:</strong> (ยังอยู่ใน Console ของ gitlab-runner)
    <pre><code>nano /etc/gitlab-runner/config.toml</code></pre>
    (หากไม่มี nano ให้ติดตั้งก่อน:
    <code>apt-get update && apt-get install -y nano</code>)
  </li>
  <li>
    <strong>ปรับแก้ค่าในส่วน <code>[runners.docker]</code> ดังนี้:</strong>
    <pre><code class="language-toml">[[runners]]
  name = "docker-runner-01"
  url = "https://gitlab.vecskill.ovec"
  # ... (ค่าอื่นๆ ที่ gitlab-runner register สร้างไว้ให้แล้ว เช่น id, token, token_obtained_at ไม่ต้องแก้) ...
  executor = "docker"
  [runners.docker]
    tls_verify = false  # (1) ปิดตรวจสอบ SSL หากใช้ Self-signed/Internal CA
    image = "docker:24.0.5"
    privileged = true   # (2) สำคัญที่สุด! ต้องเป็น true เพื่อให้ Build Docker Image (Docker-in-Docker) ได้
    disable_entrypoint_overwrite = false
    oom_kill_disable = false
    disable_cache = false
    volumes = ["/cache", "/var/run/docker.sock:/var/run/docker.sock"] # (3) Mount Socket
    pull_policy = ["if-not-present"] # (4) ช่วยประหยัดเน็ต ไม่โหลด image ซ้ำถ้ามีอยู่แล้ว
    # ...</code></pre>
  </li>
  <li>
    <strong>(ทางเลือก) แก้ปัญหา HTTPS/Clone ไม่ได้:</strong><br />
    หาก Runner ฟ้องว่า Clone ไม่ได้เพราะ SSL Error ให้เพิ่มบรรทัดนี้ในส่วน
    <code>[[runners]]</code> (ด้านบน):
    <pre><code>clone_url = "http://gitlab" # บังคับให้ Clone ผ่านชื่อ Container ในวงภายใน</code></pre>
  </li>
  <li>
    กด `Ctrl+X` > `Y` > `Enter` เพื่อบันทึก แล้ว Restart Runner:
    <pre><code>gitlab-runner restart</code></pre>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>5. ทดสอบการใช้งาน (Pipeline Test)</h2>
<p>สร้างไฟล์ <code>.gitlab-ci.yml</code> ในโปรเจกต์ของคุณเพื่อทดสอบ</p>
<pre><code class="language-yaml">stages:
  - test
  - build

test-job:
  stage: test
  tags:
    - docker # ต้องตรงกับ Tags ของ Runner
  script:
    - echo "Hello from GitLab Runner!"
    - docker info # ทดสอบว่าเรียกใช้ Docker ได้ไหม

build-job:
  stage: build
  tags:
    - docker
  image: docker:24.0.5
  script:
    - echo "Building Docker Image..."
    # ใส่คำสั่ง build จริงที่นี่
</code></pre>
<p>
  เมื่อ Push ไฟล์นี้ขึ้น GitLab ให้ไปดูที่เมนู
  <strong>Build > Pipelines</strong> ถ้าเห็นเครื่องหมายถูกสีเขียว แสดงว่า Runner ของคุณพร้อมทำงานแล้ว! ✅
</p>
