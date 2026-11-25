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
  <li>คลิกที่ปุ่ม <strong>Create instance runner</strong> (หรือมองหา Registration Token ในเวอร์ชันเก่า)</li>
  <li>กำหนด Tags (เช่น <code>docker</code>, <code>linux</code>) และกดสร้าง</li>
  <li><strong>คัดลอก Token ที่ได้เก็บไว้</strong> (เราจะต้องใช้ในขั้นตอนที่ 3)</li>
</ol>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-01.png" alt="Admin Area" class="content-image" />
    <p>หน้าจอแสดง Registration Token ใน GitLab Admin Area</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-02.png" alt="CI/CD -> Runners" class="content-image" />
    <p>หน้าจอแสดง CI/CD -> Runners</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-03.png" alt="Create instance runner" class="content-image" />
    <p>หน้าจอแสดง Create instance runner</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-04.png" alt="Tags docker" class="content-image" />
    <p>หน้าจอแสดง Tags สำหรับ Docker</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-05.png" alt="Create runner" class="content-image" />
    <p>หน้าจอแสดง Create runner</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-06.png" alt="Register runner" class="content-image" />
    <p>หน้าจอแสดง Register runner</p>
  </div>
</div>

<div class="image-container">
  <div class="image-container">
    <img src="./images/runner/runner-01-07.png" alt="Token and register command" class="content-image" />
    <p>หน้าจอแสดง Token และ ตัวอย่างการใช้งาน gitlab-runner register</p>
  </div>
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

<h2>3. การลงทะเบียน Runner (Register) ทำที่ Docker Container</h2>
<p>เมื่อ Container รันแล้ว มันยังไม่รู้ว่าจะคุยกับ GitLab ตัวไหน เราต้องเข้าไปตั้งค่ามันผ่าน Command Line</p>
<ol>
  <li>
    <strong>เข้าสู่ Console ของ Runner:</strong><br />
    ใน Portainer คลิกที่ Console ของ `gitlab-runner` หรือใช้ Terminal:
    <pre><code>docker exec -it gitlab-runner bash</code></pre>
  </li>
  <li>
    <strong>รันคำสั่ง Register:</strong> วิธีที่เร็วที่สุดคือใส่ค่าทั้งหมดเป็น Flag รวดเดียว (แทน <code>glrt-xxxxxxxxxxxxxxxxxxxx</code> ด้วย Token จริงจากขั้นตอนที่ 1):
    <pre><code>gitlab-runner register --url http://gitlab --clone-url http://gitlab --token glrt-xxxxxxxxxxxxxxxxxxxx --description "docker-runner-01" --tag-list "docker,build" --executor docker --docker-image "docker:24.0.5"</code></pre>
    หรือถ้าอยากให้ถามทีละขั้นตอนแทน (เผื่อไม่แน่ใจค่าบางอย่าง) ใช้คำสั่งเปล่าๆ นี้แล้วตอบคำถามตามด้านล่าง:
    <pre><code>gitlab-runner register</code></pre>
  </li>
  <li>
    <strong>ถ้าใช้คำสั่งเปล่า ให้ตอบคำถามทีละขั้นตอน:</strong>
    <ul>
      <li>
        <strong>Enter the GitLab instance URL:</strong>
        <code>http://gitlab</code> <strong>(แนะนำให้ใช้แบบนี้เสมอ ไม่ใช่แค่ "ทางเลือก")</strong> — ดูเหตุผลในกล่องคำอธิบายด้านล่าง
      </li>
      <li><strong>Enter the registration token:</strong> (วาง Token ที่ได้จากขั้นตอนที่ 1)</li>
      <li>
        <strong>Enter a name for the runner:</strong> ตั้งชื่อ เช่น
        <code>docker-runner-01</code> ซึ่งจะเก็บไว้ใน <code>config.toml</code> และแสดงในหน้าเว็บ GitLab
      </li>
      <li>
        <strong>Enter executor:</strong> ตั้งชื่อ executor เช่น
        <code>docker</code> ซึ่งจะบอกว่า Runner ตัวนี้จะใช้ Docker เป็น Executor (ตัวรันงาน) — ถ้าเลือกแบบอื่น เช่น Shell, Kubernetes, SSH จะไม่สามารถใช้เทคนิค Docker-in-Docker ได้
      </li>
      <li>
        <strong>Enter the default Docker image:</strong> ตัวอย่างเช่น <code>docker:24.0.5</code> (หรือ image อื่นที่ต้องการให้เป็นค่าเริ่มต้น) — Runner จะใช้ image นี้เป็นค่าเริ่มต้นสำหรับ Job ที่ไม่ได้ระบุ image ของตัวเอง
      </li>
      <li>
        <strong>Tags:</strong> <code>docker,build</code> (สำคัญ! ต้องตรงกับที่เรียกใช้ใน Pipeline) — <strong>ใน GitLab เวอร์ชันใหม่ (ที่สร้าง Runner ผ่านปุ่ม "Create instance runner" แล้วได้ Token ขึ้นต้นด้วย <code>glrt-</code>)
          ขั้นตอน Register อาจไม่ถามคำถามนี้เลย</strong> เพราะ Tags ถูกผูกไว้กับ Token ตั้งแต่ตอนสร้างในหน้าเว็บแล้ว — ถ้าไม่ได้ตั้งไว้ตอนนั้น ให้ไปเพิ่มทีหลังตามขั้นตอนถัดไปด้านล่าง (อย่าข้าม ไม่งั้น Job จะค้างสถานะ Pending)
      </li>
      <li>
        <strong>Maintenance note:</strong> (กด Enter ข้าม — หากมีคำถามอื่นๆ นอกเหนือจากนี้ก็กด Enter ข้ามไปก่อนได้เช่นกัน สามารถแก้ไขทีหลังได้ใน <code>config.toml</code> หรือหน้าเว็บ GitLab)
      </li>
    </ul>
  </li>
  <li>
    <strong>ตรวจสอบ/เพิ่ม Tag ผ่านหน้าเว็บ GitLab (บังคับ ทำทุกครั้ง):</strong><br />
    ไม่ว่าขั้นตอนก่อนหน้าจะถาม Tags หรือไม่ ให้เข้าไปที่ <strong>Admin Area</strong> &gt; <strong>CI/CD</strong> &gt; <strong>Runners</strong> แล้วหา Runner ที่เพิ่ง Register คลิกปุ่มแก้ไข (ไอคอนรูปดินสอ)
    พิมพ์ <code>docker</code> ลงในช่อง <strong>Tags</strong> (ติ๊ก <strong>Run untagged jobs</strong> ด้วยก็ได้ถ้าต้องการ) แล้วกด <strong>Save changes</strong> — ถ้าข้ามขั้นตอนนี้ Job ใน <code>.gitlab-ci.yml</code> ที่ระบุ
    <code>tags: [docker]</code> จะค้างสถานะ <strong>Pending</strong> ตลอดไปเพราะหา Runner ที่ตรงกับ Tag ไม่เจอ
  </li>

  <div class="image-container">
    <img src="./images/runner/runner-03-01.png" alt="ตรวจสอบ/เพิ่ม Tag" class="content-image" />
    <p>หน้าจอแสดง ตรวจสอบ/เพิ่ม Tag</p>
  </div>
  <div class="image-container">
    <img src="./images/runner/runner-03-02.png" alt="Save changes" class="content-image" />
    <p>หน้าจอแสดง Save changes</p>
  </div>
  <div class="image-container">
    <img src="./images/runner/runner-03-03.png" alt="การคลิกเลือก Run untagged jobs" class="content-image" />
    <p>หน้าจอแสดงการคลิกเลือก Run untagged jobs</p>
  </div>
  <div class="image-container">
    <img src="./images/runner/runner-03-04.png" alt="ผลการตรวจสอบ Tag และ Un tagged jobs" class="content-image" />
    <p>หน้าจอแสดง ผลการตรวจสอบ Tag และ Un tagged jobs</p>
  </div>
</ol>
<p>หากขึ้นข้อความ <code>Runner registered successfully.</code> แสดงว่าสำเร็จ! และสถานะ Runner หน้าเว็บควรขึ้นจุดสีเขียว (Online) พร้อมป้ายกำกับ <code>docker</code></p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>ทำไมต้องใช้ <code>http://gitlab</code> ไม่ใช่ <code>https://gitlab.vecskill.ovec</code>?</strong><br />
  ไม่ใช่แค่เรื่องสะดวก แต่เป็นข้อบังคับทางเทคนิค: ใน Stack ของ GitLab (บท
  <a href="#" onclick="document.querySelector('a[data-page=\'gitlab\']').click();">การติดตั้ง GitLab</a>) เราตั้งค่า <code>hostname: 'gitlab.vecskill.ovec'</code> ไว้ที่ตัว Container เอง
  ทำให้ DNS ภายใน Network <code>app_net</code> จับคู่ชื่อ <code>gitlab.vecskill.ovec</code> เข้ากับ IP ของ Container GitLab <strong>โดยตรง</strong> (ไม่ผ่าน Caddy)
  แต่ Container GitLab เปิดรับเฉพาะ Port <code>80</code> เท่านั้น (Port <code>443</code> เป็นหน้าที่ของ Caddy ที่อยู่คนละ Container) ดังนั้นไม่ว่าจะตั้งค่า HTTPS ผ่าน Caddy ไว้แล้วหรือยัง
  การเรียก <code>https://gitlab.vecskill.ovec</code> <strong>จากภายใน Network <code>app_net</code> เดียวกัน</strong> (เช่น จาก Container ของ Runner) จะได้ Error ประมาณ
  <code>dial tcp &lt;IP ภายใน&gt;:443: connect: connection refused</code> เสมอ การใช้ <code>http://gitlab</code> (ชื่อ Service ตรงๆ ไม่มี Domain) จึงเป็นทางเดียวที่ถูกต้องสำหรับการเชื่อมต่อ
  "ภายใน Network เดียวกัน" นี้ — คนละกรณีกับตอนเปิดจาก Browser ของ Client ซึ่งอยู่คนละ Network กับ Caddy เลยเรียก <code>https://gitlab.vecskill.ovec</code> ได้ตามปกติ
</p>

<hr style="margin: 40px 0" />

<h2>4. การปรับแต่ง Config (สำคัญมาก!)</h2>
<p>
  ค่าเริ่มต้นของการ Register ยังไม่เพียงพอสำหรับการ Build Docker Image ด้วยเทคนิค Docker-in-Docker (ตามที่ใช้ในบทการทำ CI/CD) เราต้องแก้ไขไฟล์
  <code>config.toml</code> เพื่อเปิดสิทธิ์ <code>privileged</code> ให้ Runner ก่อน ไม่เช่นนั้น Job ที่เรียกใช้ <code>docker:dind</code> จะ Build/Push Image ไม่ได้
</p>
<ol>
  <li>
    <strong>แก้ไขไฟล์ config:</strong> (ยังอยู่ใน Console ของ gitlab-runner)
    (หากไม่มี nano ให้ติดตั้งก่อน ซึ่งใน Container ของ Runner ไม่มี Editor ติดตั้งมาให้ — ใช้คำสั่ง:
    <code>apt-get update && apt-get install -y nano</code>)
    <pre><code>nano /etc/gitlab-runner/config.toml</code></pre>
  </li>
  <li>
    <strong>ปรับแก้ค่าในส่วน <code>[runners.docker]</code> ดังนี้:</strong>
    <pre><code class="language-toml">[[runners]]
  name = "docker-runner-01"
  url = "http://gitlab"       # ต้องเป็น http://gitlab ตามเหตุผลในขั้นตอนที่ 3 (ไม่ใช่ https://gitlab.vecskill.ovec)
  clone_url = "http://gitlab" # บังคับให้ Job Clone โค้ดผ่านชื่อ Container ในวงภายในด้วยเหตุผลเดียวกัน
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
    network_mode = "app_net" # (4) สำคัญมาก! ถ้าไม่ใส่ Container ลูกที่รันงานแต่ละ Job จะถูกสร้างใน Network แยกต่างหาก
                              #     มองไม่เห็น gitlab/registry เลย ทำให้ Clone/Push ไม่ได้แม้ Register สำเร็จแล้วก็ตาม
    # extra_hosts = ["gitlab.vecskill.ovec:10.20.30.101", "registry.vecskill.ovec:10.20.30.101"]
    #   (5) (ทางเลือก) ใช้เฉพาะกรณี DNS ภายใน (Technitium) ยังไม่พร้อม หรือต้องการ Fallback ไว้เผื่อ DNS ล่ม
    pull_policy = ["if-not-present"] # (6) ช่วยประหยัดเน็ต ไม่โหลด image ซ้ำถ้ามีอยู่แล้ว
    # ...</code></pre>
    <div class="image-container">
      <img src="./images/runner/runner-04-01.png" alt="config.toml" class="content-image" />
      <p>หน้าจอแสดงตัวอย่าง config.toml</p>
    </div>

    <p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
      <strong>ทำไมต้องมี <code>network_mode = "app_net"</code>:</strong> Executor แบบ <code>docker</code> ของ GitLab Runner ปกติจะสร้าง Network แยกใหม่ให้แต่ละ Job โดยอัตโนมัติ (ไม่ใช่ <code>app_net</code>)
      ถ้าไม่ระบุค่านี้ Container ที่รันงานจริง (ไม่ใช่ตัว <code>gitlab-runner</code> container ที่เราเพิ่ง Deploy) จะต่อเน็ตเวิร์กคนละวงกับ GitLab/Registry โดยสิ้นเชิง ทำให้ <code>git clone http://gitlab/...</code>
      หรือ <code>docker push registry.vecskill.ovec/...</code> ที่รันอยู่ใน Job ล้มเหลวด้วย Error ประมาณ <code>could not resolve host</code> แม้ว่าตัว Runner เองจะ Register สำเร็จและสถานะ Online ก็ตาม
    </p>
  </li>
  <li>
    กด `Ctrl+X` > `Y` > `Enter` เพื่อบันทึก แล้วตรวจสอบไฟล์ก่อน Restart:
    <pre><code>gitlab-runner verify</code></pre>
    ควรเห็นข้อความ <code>Verifying runner... is valid</code> โดยไม่มี Error จากนั้น <strong>ออกจาก Container กลับไปที่เครื่อง Host ก่อน แล้วค่อยสั่ง Restart ที่เครื่อง Host</strong>
    (ห้ามสั่ง <code>gitlab-runner restart</code> จากข้างในนะ — ดูเหตุผลในกล่องด้านล่าง):
    ออกจาก Docker Container
    <pre><code>exit</code></pre>
    กลับมาที่หน้าจอ Console ของ Host แล้วสั่ง Restart Container ของ Runner:
    <pre><code>docker restart gitlab-runner</code></pre>
    <p style="background-color: #f8d7da; border-left: 5px solid #d9534f; padding: 15px">
      <strong>ทำไม <code>gitlab-runner restart</code> ใช้ไม่ได้:</strong> คำสั่งนี้ใช้ได้เฉพาะตอนติดตั้งแบบ Service/systemd บน Linux ปกติเท่านั้น แต่ภายใน Docker Container ตัวโปรแกรม
      <code>gitlab-runner</code> ทำงานเป็น Process หลัก (PID 1) โดยตรง ไม่มี systemd หรือ init.d ให้สั่งงาน การรัน <code>gitlab-runner restart</code> จากข้างใน Container จึงจะ Error
      <code>FATAL: Failed to restart gitlab-runner: exit status 1</code> เสมอ — วิธีที่ถูกต้องคือออกมาสั่ง <code>docker restart gitlab-runner</code> จากเครื่อง Host แทน (เท่ากับ Restart
      ทั้ง Process ของ Container ใหม่ ซึ่งจะโหลด <code>config.toml</code> ที่เพิ่งแก้ไขขึ้นมาใหม่เช่นกัน)
    </p>
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

<hr style="margin: 40px 0" />

<h2>6. เชื่อมต่อไปยัง Production Server (Server เครื่องที่ 2)</h2>
<p>
  โครงสร้างทั่วไปของคู่มือนี้แยกเป็น 2 เครื่อง: <strong>Server เครื่องที่ 1</strong> รัน GitLab + Registry + Runner (ใช้ IP ภายใน <code>10.20.30.101</code> ตามบท
  <a href="#" onclick="document.querySelector('a[data-page=\'vmware-quickstart\']').click();">Quick Start (VMWare)</a>) และ <strong>Server เครื่องที่ 2</strong> ทำหน้าที่เป็น Production
  ที่ GitLab Runner จะ SSH เข้าไปสั่ง Deploy ตามบท <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a> (ใช้ IP ตัวอย่าง
  <code>192.168.100.102</code> ตาม <code>PRODUCTION_IP</code> ในบทนั้น) ขั้นตอนที่ผ่านมาทำให้ Runner คุยกับ GitLab/Registry ได้แล้ว แต่ยังมีอีกจุดที่ต้องตั้งค่าเพิ่ม คือ
  <strong>ทำให้เครื่อง Production (Server 2) ดึง (Pull) Docker Image จาก Registry ของ Server 1 ได้</strong> ซึ่งทำได้ 2 แบบ เลือกใช้แบบใดแบบหนึ่งตามที่สนามแข่งขัน/หน่วยงานของคุณตั้งค่าไว้
</p>

<h3>6.1 กรณีไม่ใช้ HTTPS (Insecure Registry — ตั้งค่าเร็ว เหมาะกับทดสอบ/Lab ภายใน)</h3>
<p>
  วิธีนี้ตรงกับที่อธิบายไว้แล้วในบท <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a> หัวข้อ "1.1 ตั้งค่า Insecure Registry" และวิธีสำรองในบท
  <a href="#" onclick="document.querySelector('a[data-page=\'registry\']').click();">การติดตั้ง Registry & UI</a> — ข้อควรระวังเฉพาะฝั่ง Runner ที่มักถูกมองข้ามคือ:
</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>ข้อควรระวัง: การตั้งค่าฝั่ง Build กับฝั่ง Pull ไม่ได้ใช้วิธีเดียวกัน:</strong> ถ้า Job Build ของคุณใช้เทคนิค <strong>Docker-in-Docker</strong> (ประกาศ <code>services: [docker:26-dind]</code> ใน
  <code>.gitlab-ci.yml</code> ตามบท <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a>) Daemon ที่ใช้ Build/Push จะเป็น Container ชั่วคราวใหม่ทุก Job
  ซึ่ง<strong>ไม่อ่านไฟล์ <code>/etc/docker/daemon.json</code> ของเครื่อง Host เลย</strong> ต้องส่ง Flag <code>--insecure-registry=registry.vecskill.ovec:5000</code> ให้ Service นั้นโดยตรงแทน (ดูตัวอย่างเต็มในบท CI/CD)
  — ส่วนไฟล์ <code>/etc/docker/daemon.json</code> แบบปกติยังจำเป็นอยู่ แต่ใช้กับ <strong>เครื่อง Production (Server 2)</strong> ที่รัน <code>docker compose pull</code> ผ่าน Daemon จริงของเครื่องนั้น (ไม่ใช่ dind)
</p>

<h3>6.2 กรณีใช้ HTTPS ผ่าน Caddy Internal CA (แนะนำ ปลอดภัยกว่า)</h3>
<p>
  วิธีนี้ใช้ร่วมกับบท <a href="#" onclick="document.querySelector('a[data-page=\'caddy-internal-ca\']').click();">การทำ HTTPS ภายใน</a> ซึ่งมีขั้นตอนติดตั้ง Root CA ของ Caddy ไว้ครบแล้วทั้ง 2 เครื่อง
  สรุปเฉพาะส่วนที่เกี่ยวกับ Runner/Production:
</p>
<ul>
  <li>
    <strong>ขั้นตอนที่ 3.4 (ติดตั้งบนเครื่อง Production) เท่านั้นที่จำเป็นสำหรับ Runner:</strong> ทำบน <strong>Server 2</strong> เพื่อให้ <code>docker compose pull</code> ตอน Deploy ดึง Image จาก
    <code>https://registry.vecskill.ovec</code> ได้โดยไม่ติด TLS Error
  </li>
  <li>
    <strong>ไม่จำเป็นต้องทำขั้นตอนที่ 3.3 บน Server 1 เพื่อการ Build/Push:</strong> เพราะ Job Build ใช้เทคนิค Docker-in-Docker (Container ชั่วคราวที่ถูกสร้าง/ทำลายทุก Job) การให้ Container ชั่วคราวนี้เชื่อถือ
    Root CA ทำได้ยากและไม่คุ้มค่า คู่มือนี้จึงแนะนำให้ฝั่ง Build ใช้ Flag <code>--insecure-registry</code> เสมอ (ดูรายละเอียดและตัวอย่างเต็มในบท
    <a href="#" onclick="document.querySelector('a[data-page=\'cicd\']').click();">การใช้งาน Gitlab CI/CD</a>) แม้ส่วนที่เหลือของระบบจะใช้ HTTPS ก็ตาม
  </li>
</ul>
<p>
  เมื่อทำครบทั้ง 2 ขั้นตอนแล้ว ให้เปลี่ยน <code>IMAGE_NAME</code> ในบท CI/CD จาก <code>registry.vecskill.ovec:5000/...</code> (แบบ Insecure) เป็น <code>registry.vecskill.ovec/...</code>
  (ไม่มี Port ต่อท้าย — ตามวิธีที่แนะนำในบท Registry & UI) และลบการตั้งค่า <code>insecure-registries</code> ออกจากทั้ง 2 เครื่องได้เลย เพราะไม่จำเป็นอีกต่อไป
</p>

<h3>6.3 เช็กลิสต์ตรวจสอบความพร้อมก่อนเริ่มงาน CI/CD</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>รายการตรวจสอบ</th>
      <th>คำสั่ง / ตำแหน่งที่ตรวจ</th>
      <th>ผลลัพธ์ที่ถูกต้อง</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>สถานะ Runner Online + มี Tag</td>
      <td>หน้าเว็บ GitLab → Runners</td>
      <td>จุดสีเขียว (Online) + มี Tag <code>docker</code></td>
    </tr>
    <tr>
      <td>Config ของ Runner ถูกต้อง</td>
      <td><code>gitlab-runner verify</code> (ใน Container)</td>
      <td><code>Verifying runner... is valid</code></td>
    </tr>
    <tr>
      <td>Firewall ระหว่าง Server 1 ↔ Server 2 (SSH)</td>
      <td><code>nc -zv 192.168.100.102 22</code> (จาก Server 1)</td>
      <td>Connection succeeded / open</td>
    </tr>
    <tr>
      <td>ทดสอบ Pull Image ที่ Production</td>
      <td><code>docker pull registry.vecskill.ovec/my-image:latest</code> (หรือ <code>:5000/...</code> ถ้าใช้ Insecure)</td>
      <td>ดาวน์โหลดสำเร็จ ไม่ติด SSL Error</td>
    </tr>
  </tbody>
</table>