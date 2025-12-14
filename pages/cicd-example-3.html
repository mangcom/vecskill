<h1>ตัวอย่าง CI/CD Pipeline แบบที่ 2 (Runner Setup & SSH Deployment)</h1>
<p>
    หน้านี้เป็นตัวอย่างการทำระบบ CI/CD แบบครบวงจร ตั้งแต่การติดตั้ง GitLab Runner ด้วย Docker, การ Config ให้รองรับ Docker-in-Docker, การจัดการ SSH Key เพื่อ Deploy ไปยัง Production (IP: 192.168.20.109) และไฟล์ Config ที่ใช้งานจริง
</p>

<hr style="margin: 40px 0;">

<h2>1. การติดตั้ง GitLab Runner (Docker Compose)</h2>
<p>
    เราจะติดตั้ง Runner โดยใช้ Docker Compose และตั้งค่า Network ให้มองเห็น GitLab Server (192.168.20.106)
</p>

<h3>1.1 สร้าง Volume สำหรับเก็บ Config</h3>
<p>
    ตัวอย่างนี้จะสร้าง Volume ชื่อ <code>gitlab_runner_config</code> เพื่อเก็บข้อมูล Config ของ Runner
    <p><code>root@dev:/# docker volume create gitlab_runner_config</code></p>
</p>
<pre><code>docker volume create gitlab_runner_config</code></pre>

<h3> สร้างโฟลเดอร์ชื่อ gitlab-runner ใต้ root directory เพื่อเก็บไฟล์ docker-compose.yml</h3>
<p>
    <p><code>root@dev:/# mkdir -p /gitlab-runner<br>
root@dev:/# cd gitlab-runner/</code>
</p>
</p>
<pre><code>mkdir -p /gitlab-runner
cd gitlab-runner/
</code></pre>

<h3>เปิดไฟล์ docker-compose.yml</h3>
<p>
     root@dev:/gitlab-runner# vi docker-compose.yml
</p>
<pre><code>vi docker-compose.yml</code></pre>

<h3>ไฟล์ docker-compose.yml สำหรับ Runner</h3>
<div class="code-container">
<pre><code class="language-yaml">version: '3.8'

services:
  gitlab-runner:
    image: gitlab/gitlab-runner:latest
    container_name: gitlab-runner
    restart: always
    networks:
      - app_net
    volumes:
      # เชื่อมต่อโฟลเดอร์ /gitlab-runner บน Host ไปยัง Config ภายใน Container
      - /gitlab-runner:/etc/gitlab-runner
      # (สำคัญ) เชื่อมต่อ Docker Socket
      - /var/run/docker.sock:/var/run/docker.sock

    extra_hosts:
      # ชี้ชื่อ Host ไปยัง IP ของ GitLab/Registry 192.168.20.106
      - "gitlab.vecskill.ovec:192.168.20.106"
      - "registry.vecskill.ovec:192.168.20.106

networks:
  app_net:
    external: true
</code></pre>
</div>
<p>สั่งรันด้วยคำสั่ง: <code>docker compose up -d</code></p>
<pre><code>docker compose up -d</code></pre>

<h3>1.2 แก้ปัญหา Insecure Registry (ทำทั้งเครื่อง Host และ Production )</h3>
<p>แก้ไขไฟล์ <code>/etc/docker/daemon.json</code> เพื่อให้ Docker ยอมรับ Registry แบบ HTTP</p>
<pre><code> vi /etc/docker/daemon.json</code></pre>
<p>จากนั้นแก้ไขโดยการเพิ่ม</p>
<pre><code class="language-json">{
  "insecure-registries": ["192.168.20.106:5000"]
}</code></pre>
<p>จากนั้นรีสตาร์ท Docker: <code>sudo systemctl restart docker</code></p>
<pre><code>sudo systemctl restart docker</code></pre>
<hr style="margin: 40px 0;">

<h2>2. การลงทะเบียน Runner (Register)</h2>

<h3>2.1 เข้าสู่ Console ของ Runner:</h3>
<p>
    ใน Portainer คลิกที่ Console ของ `gitlab-runner` หรือใช้ Terminal:
</p>
<pre><code>docker exec -it gitlab-runner bash</code></pre>

<h3>2.2 รันคำสั่ง Register:</h3>
<p>
    ใน Portainer คลิกที่ Console ของ `gitlab-runner` หรือใช้ Terminal:
</p>
<pre><code>gitlab-runner register</code></pre>
<p>
    ตอบคำถามทีละขั้นตอน
    <pre><code>Enter the GitLab instance URL (for example, https://gitlab.com/):
http://192.168.20.106/ 

ไส่Token gitlabrunner

Enter the registration token: 
GR1348941HsqstdRpWJxWeyQB75Fz

ตั้งชื่อ Description: 

Enter a description for the runner:
[82cd6a96cd8a]: test-runner



สำคัญ! ต้องตรงกับที่เรียกใช้ใน Pipeline Tags: docker,build

Enter tags for the runner (comma-separated):
docker,build

Maintenance note:(กด Enter ข้าม)

Executor: พิมพ์ว่า docker

Default Docker image:  พิมพ์ว่า docker:29
Enter the default Docker image (for example, ruby:3.3):
docker:27
หากขึ้นข้อความ Runner registered successfully. แสดงว่าสำเร็จ!
แล้วก็ exit ออก
root@82cd6a96cd8a:/# exit
</code></pre>
</p>

<h3>2.3 การปรับแต่ง Config (สำคัญมาก!) เข้าสู่ Console ของ Runner</h3>
<pre><code>docker exec -it gitlab-runner bash</code></pre>
<p>(หากไม่มี nano ให้ติดตั้งก่อน: apt-get update && apt-get install -y nano)</p>
<pre><code>apt-get update && apt-get install -y nano</code></pre>
<p>แก้ไขไฟล์ config: (ยังอยู่ใน Console ของ gitlab-runner)</p>
<pre><code>nano /etc/gitlab-runner/config.toml</code></pre>
<p>แก้ไขส่วน /etc/gitlab-runner/config.toml ให้เป็นดังนี้:</p>

<pre><code> token_obtained_at = 2025-12-10T15:11:11Z
  token_expires_at = 0001-01-01T00:00:00Z
  executor = "docker"
  [runners.cache]
    MaxUploadedArchiveSize = 0
    [runners.cache.s3]
    [runners.cache.gcs]
    [runners.cache.azure]
  [runners.docker]
    tls_verify = false
    image = "docker:27"
    privileged = true
    disable_entrypoint_overwrite = false
    oom_kill_disable = false
    disable_cache = false
    volumes = ["/cache", "/var/run/docker.sock:/var/run/docker.sock"]
    pull_policy = ["if-not-present"]
    shm_size = 0
    network_mtu = 0

[[runners]]
  name = "test-runner"
  url = "http://192.168.20.106/"
  id = 8
  token = "glrtr-c54z9yp07CV-hw5tT6rWlW86MQpwOjIKdDozCw.01.1214lsdkx"
  token_obtained_at = 2025-12-11T03:43:44Z
  token_expires_at = 0001-01-01T00:00:00Z
  executor = "docker"
  [runners.cache]
    MaxUploadedArchiveSize = 0
    [runners.cache.s3]
    [runners.cache.gcs]
    [runners.cache.azure]
  [runners.docker]
    tls_verify = false  # (1) ปิดตรวจสอบ SSL หากใช้ Self-signed/Internal CA
    image = "docker:27"
    privileged = true  # (2) สำคัญ! ต้องเป็น true เพื่อให้ build docker ได้
    disable_entrypoint_overwrite = false
    oom_kill_disable = false
    disable_cache = false
    volumes = ["/cache", "/var/run/docker.sock:/var/run/docker.sock" ]
    pull_policy = ["if-not-present"] # (4) ช่วยประหยัดเน็ต ไม่โหลด image ซ้ำ
    shm_size = 0
    network_mtu = 0
</code></pre>
<p>
    แก้ไฟล์เสร็จ เซฟแล้วก็ออกมา
    <pre><code>exit</code></pre>
</p>
<p>
   แล้วก็ Restart Runner
    <pre><code>docker restart gitlab-runner</code></pre>
</p>
















<h2>2. การ Config Runner และ In-Container SSH Key</h2>

<h3>2.1 ปรับแต่ง config.toml (สำคัญมากสำหรับการ Build Image)</h3>
<p>
    หลังจาก Register Runner เสร็จแล้ว (ใช้ Tag: <code>docker,build</code>) ต้องเข้าไปแก้ไฟล์ Config ใน Container เพื่อเปิดสิทธิ์ <code>privileged</code>
</p>
<pre><code>docker exec -it gitlab-runner nano /etc/gitlab-runner/config.toml</code></pre>

<div class="code-container">
<pre><code class="language-toml">[[runners]]
  name = "test-runner"
  url = "http://192.168.20.106/"
  executor = "docker"
  [runners.docker]
    tls_verify = false            # (1) ปิดตรวจสอบ SSL
    image = "docker:27"
    privileged = true             # (2) สำคัญ! ต้องเป็น true เพื่อให้ build docker ได้
    pull_policy = ["if-not-present"] # (3) ช่วยประหยัดเน็ต ไม่โหลด image ซ้ำ
    volumes = ["/cache", "/var/run/docker.sock:/var/run/docker.sock"]</code></pre>
</div>
<p>แก้ไขเสร็จแล้วให้สั่ง: <code>docker restart gitlab-runner</code></p>

<h3>2.2 การสร้าง SSH Key ภายใน Runner Container</h3>
<p>เพื่อให้ Runner สามารถ SSH ไปหา Production ได้ เราต้องสร้าง Key ภายใน Container ของ Runner เอง</p>
<ol>
    <li>
        <strong>เข้าไปใน Container:</strong>
        <pre><code>docker exec -it gitlab-runner sh</code></pre>
    </li>
    <li>
        <strong>ติดตั้ง ssh-keygen และสร้าง Key:</strong>
        <pre><code>apk add --no-cache openssh-client
mkdir -p /home/gitlab-runner/.ssh
ssh-keygen -t rsa -b 4096 -C "gitlab-ci" -f /home/gitlab-runner/.ssh/id_rsa</code></pre>
        (กด Enter ข้าม Passphrase ไปเลย)
    </li>
    <li>
        <strong>ดู Public Key (เพื่อเอาไปใส่ Production):</strong>
        <pre><code>cat /home/gitlab-runner/.ssh/id_rsa.pub</code></pre>
        Copy เก็บไว้ (ขึ้นต้นด้วย <code>ssh-rsa ...</code>)
    </li>
    <li>
        <strong>ดู Private Key (เพื่อเอาไปใส่ GitLab Variable):</strong>
        <pre><code>cat /home/gitlab-runner/.ssh/id_rsa</code></pre>
        Copy ทั้งก้อน (รวม BEGIN/END) ไปใส่ใน GitLab -> Settings -> CI/CD -> Variables ชื่อ <code>SSH_PRIVATE_KEY</code>
    </li>
</ol>

<hr style="margin: 40px 0;">

<h2>3. การเตรียม Production Server (192.168.20.109)</h2>

<h3>3.1 วาง Public Key</h3>
<p>ที่เครื่อง Production ให้เอา Public Key ที่ได้จากข้อ 2.2 มาใส่ในไฟล์ <code>authorized_keys</code></p>
<pre><code>mkdir -p ~/.ssh
nano ~/.ssh/authorized_keys  # วาง Public Key ลงไป
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys</code></pre>

<h3>3.2 ไฟล์ docker-compose.yml (สำหรับรัน App)</h3>
<p>สร้างไฟล์นี้ไว้ที่ <code>~/skills/docker-compose.yml</code></p>
<div class="code-container">
<pre><code class="language-yaml">version: "3.9"

services:
  db:
    image: mysql:8
    container_name: db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: skills_docdb
      MYSQL_USER: user1
      MYSQL_PASSWORD: user1_1234
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - fsnet

  phpmyadmin:
    image: phpmyadmin:latest
    container_name: phpmyadmin
    environment:
      PMA_HOST: db
    ports:
      - "8080:80"
    networks:
      - fsnet

  api:
    image: 192.168.20.106:5000/backend_tag:latest
    container_name: api
    ports:
      - "7000:7000"
    environment:
      DB_HOST: db
      DB_PORT: 3306
      DB_NAME: skills_docdb
      DB_USER: user1
      DB_PASSWORD: user1_1234
    networks:
      - fsnet

  frontend:
    image: 192.168.20.106:5000/frontend_tag:latest
    container_name: frontend
    ports:
      - "3000:3000"
    environment:
      NUXT_PUBLIC_API_BASE: "http://192.168.20.109:7000"
    networks:
      - fsnet

volumes:
  db_data:

networks:
  fsnet:
    driver: bridge</code></pre>
</div>

<hr style="margin: 40px 0;">

<h2>4. ไฟล์ Pipeline Configuration (.gitlab-ci.yml)</h2>
<p>Script สำหรับ Build Image และ Deploy ไปยัง Production (รวม Test Docker)</p>

<div class="code-container">
<pre><code class="language-yaml">stages:
  - build_push
  - deploy

variables:
  REGISTRY_URL: "192.168.20.106:5000"
  IMAGE_API: "$REGISTRY_URL/backend_tag"
  IMAGE_FRONT: "$REGISTRY_URL/frontend_tag"
  DOCKER_TLS_CERTDIR: ""

# ========================
# Test Docker & Build Push
# ========================
test-docker:
  stage: build_push
  image: docker:29
  tags:
    - docker
  script:
    - echo "Testing Docker..."
    - docker info
  allow_failure: false

build_push:
  stage: build_push
  image: docker:29
  tags:
    - docker
  script:
    - echo "Building images..."
    # Build โดยระบุ Tag เป็น Commit SHA (เพื่อให้ไม่ซ้ำ)
    - docker build -t "$IMAGE_API:$CI_COMMIT_SHORT_SHA" backend
    - docker build -t "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA" frontend
    
    # Tag เป็น Latest ด้วย
    - docker tag "$IMAGE_API:$CI_COMMIT_SHORT_SHA" "$IMAGE_API:latest"
    - docker tag "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA" "$IMAGE_FRONT:latest"
    
    # Push ขึ้น Registry
    - docker push "$IMAGE_API:$CI_COMMIT_SHORT_SHA"
    - docker push "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA"
    - docker push "$IMAGE_API:latest"
    - docker push "$IMAGE_FRONT:latest"
  only:
    - main

# ========================
# Deploy to Production
# ========================
deploy:
  stage: deploy
  image: alpine:3.20
  tags:
    - docker
  before_script:
    - echo "Installing SSH client..."
    - apk add --no-cache openssh-client
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    # ดึง Private Key จาก Variable มาสร้างไฟล์
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    # Scan Host Key เพื่อกันถาม yes/no
    - ssh-keyscan -H 192.168.20.109 >> ~/.ssh/known_hosts 2>/dev/null || true
  script:
    - echo "Deploying to production..."
    - ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 production@192.168.20.109 "
        echo 'Connected to production' &&
        cd ~/skills &&
        echo 'Pulling latest images...' &&
        docker compose pull &&
        echo 'Starting containers...' &&
        docker compose up -d &&
        echo 'Deploy complete!'
      "
  only:
    - main</code></pre>
</div>

<hr style="margin: 40px 0;">

<h2>5. เทคนิคพิเศษ: Dockerfile สำหรับ Nuxt (Frontend)</h2>
<p>
    หากเจอปัญหา <code>Permission denied</code> หรือ <code>sh: nuxt: not found</code> ตอน Build Frontend ให้ใช้ Dockerfile สูตรนี้ครับ
</p>
<div class="code-container">
<pre><code class="language-dockerfile">FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci || npm install

COPY . .

# ตั้งค่า Environment Variable ตอน Build
ARG NUXT_PUBLIC_API_BASE=http://192.168.20.109:7000
ENV NUXT_PUBLIC_API_BASE=${NUXT_PUBLIC_API_BASE}

# --- จุดสำคัญ: แก้ Permission ---
# ให้สิทธิ์ executable ไฟล์ใน .bin ป้องกัน permission denied
RUN chmod +x node_modules/.bin/nuxt || true
RUN chmod -R +x node_modules/.bin || true

RUN npm run build

# --------------------
# Production image
# --------------------
FROM node:20-alpine

WORKDIR /app

COPY --from=build /app/.output ./.output
COPY --from=build /app/package.json ./package.json

RUN npm install --omit=dev

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]</code></pre>
</div>