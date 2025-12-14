<h1>ตัวอย่าง CI/CD แบบที่ 2: Runner Setup & SSH Deployment (Full Workflow)</h1>
<p>
    คู่มือนี้รวบรวมขั้นตอนการติดตั้ง GitLab Runner, การตั้งค่า Insecure Registry, การสร้าง SSH Key เพื่อเชื่อมต่อระหว่าง Runner และ Production, และตัวอย่างไฟล์ Pipeline ที่ใช้งานจริง
</p>

<hr style="margin: 40px 0;">

<h2>1. ติดตั้ง GitLab Runner</h2>

<h3>1.1 เตรียมพื้นที่และ Volume</h3>
<p>สร้าง Volume สำหรับเก็บ Config และสร้างโฟลเดอร์เก็บไฟล์ docker-compose</p>
<pre><code>root@dev:/# docker volume create gitlab_runner_config
root@dev:/# mkdir -p /gitlab-runner
root@dev:/# cd gitlab-runner/</code></pre>

<h3>1.2 สร้างไฟล์ docker-compose.yml</h3>
<p><code>root@dev:/gitlab-runner# vi docker-compose.yml</code></p>
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
      - "registry.vecskill.ovec:192.168.20.106"

networks:
  app_net:
    external: true</code></pre>
</div>
<p>สั่งรัน: <code>docker compose up -d</code></p>

<h3>1.3 ตั้งค่า Insecure Registry</h3>
<p>ทำบน Dev Server และ Production (เครื่อง Runner ที่มี Docker)</p>
<pre><code>vi /etc/docker/daemon.json</code></pre>
<pre><code class="language-json">{
  "insecure-registries": ["192.168.20.106:5000"]
}</code></pre>
<p>แล้ว Restart Docker: <code>sudo systemctl restart docker</code></p>

<hr style="margin: 40px 0;">

<h2>2. การลงทะเบียน Runner (Register)</h2>

<h3>2.1 เข้าสู่ Console และ Register</h3>
<pre><code>root@dev:/gitlab-runner# docker exec -it gitlab-runner bash
root@82cd6a96cd8a:/# gitlab-runner register</code></pre>

<p><strong>ตอบคำถามดังนี้:</strong></p>
<ul>
    <li><strong>GitLab instance URL:</strong> <code>http://192.168.20.106/</code></li>
    <li><strong>Token:</strong> (ใส่ Token ที่ได้จาก GitLab)</li>
    <li><strong>Description:</strong> <code>test-runner</code></li>
    <li><strong>Tags:</strong> <code>docker,build</code> <span style="color:red;">(สำคัญ! ต้องตรงกับ Pipeline)</span></li>
    <li><strong>Maintenance note:</strong> (Enter ผ่าน)</li>
    <li><strong>Executor:</strong> <code>docker</code></li>
    <li><strong>Default Docker image:</strong> <code>docker:27</code></li>
</ul>

<h3>2.2 ปรับแต่ง Config (สำคัญมาก!)</h3>
<p>ต้องแก้ไขเพื่อให้ Build Docker Image ได้</p>
<pre><code># ติดตั้ง nano (ถ้าไม่มี)
apt-get update && apt-get install -y nano

# แก้ไขไฟล์ config
nano /etc/gitlab-runner/config.toml</code></pre>

<div class="code-container">
<pre><code class="language-toml">[[runners]]
  name = "test-runner"
  url = "http://192.168.20.106/"
  executor = "docker"
  [runners.docker]
    tls_verify = false   # (1) ปิดตรวจสอบ SSL
    image = "docker:27"
    privileged = true    # (2) สำคัญ! ต้องเป็น true เพื่อให้ build docker ได้
    disable_cache = false
    volumes = ["/cache", "/var/run/docker.sock:/var/run/docker.sock"]
    pull_policy = ["if-not-present"] # (4) ช่วยประหยัดเน็ต ไม่โหลด image ซ้ำ</code></pre>
</div>
<p>แก้ไขเสร็จแล้ว Exit ออกมา แล้ว Restart Runner:</p>
<pre><code>root@dev:/gitlab-runner# docker restart gitlab-runner</code></pre>

<hr style="margin: 40px 0;">

<h2>3. การสร้าง SSH Key (เชื่อม Runner -> Production)</h2>

<h3>3.1 สร้าง Key ใน Container</h3>
<pre><code># 1. เข้าไปใน Container
docker exec -it gitlab-runner sh

# 2. ติดตั้ง openssh-client
apk add --no-cache openssh-client

# 3. สร้าง Key (กด Enter ผ่าน Passphrase)
mkdir -p /home/gitlab-runner/.ssh
ssh-keygen -t rsa -b 4096 -C "gitlab-ci" -f /home/gitlab-runner/.ssh/id_rsa

# 4. ตั้ง Permission
chmod 600 /home/gitlab-runner/.ssh/id_rsa
chmod 644 /home/gitlab-runner/.ssh/id_rsa.pub</code></pre>

<h3>3.2 ส่ง Key ไป Production</h3>
<p>ใช้วิธี <code>ssh-copy-id</code> จากเครื่อง Host (DevServer)</p>
<pre><code>ssh-copy-id -i /gitlab-runner/config/id_rsa.pub production@192.168.20.109</code></pre>
<p><em>(หมายเหตุ: Path <code>/gitlab-runner/config/</code> คือ Volume ที่เรา Mount ไว้กับ <code>/etc/gitlab-runner</code> หรือ <code>/home/gitlab-runner/.ssh</code> ขึ้นอยู่กับว่า key ถูกสร้างไว้ที่ไหน ถ้าหาไม่เจอ ให้ใช้วิธี Manual Copy ด้านล่าง)</em></p>

<h3>3.3 (ทางเลือก) วิธี Manual Copy Key</h3>
<ol>
    <li>ใน Container Runner: <code>cat /home/gitlab-runner/.ssh/id_rsa.pub</code></li>
    <li>Copy ข้อความทั้งหมด (ssh-rsa ...)</li>
    <li>ไปที่เครื่อง Production:
<pre><code>mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys  # วาง Key ลงไป
chmod 600 ~/.ssh/authorized_keys</code></pre>
    </li>
</ol>

<h3>3.4 ทดสอบเชื่อมต่อ</h3>
<pre><code>docker exec -it gitlab-runner sh -c "ssh -i /home/gitlab-runner/.ssh/id_rsa production@192.168.20.109 hostname"</code></pre>
<p>ถ้าเห็น Hostname ของ Production แสดงว่าผ่าน ✅</p>

<hr style="margin: 40px 0;">

<h2>4. เตรียม Production Server</h2>

<h3>4.1 ตรวจสอบ SSH Config</h3>
<p><code>sudo vi /etc/ssh/sshd_config</code> ต้องตั้งค่าดังนี้:</p>
<pre><code>PubkeyAuthentication yes
AuthorizedKeysFile     .ssh/authorized_keys
PasswordAuthentication yes
ChallengeResponseAuthentication no</code></pre>

<h3>4.2 เตรียมโฟลเดอร์และไฟล์ Docker Compose</h3>
<pre><code>mkdir -p ~/skills/backend
vi ~/skills/docker-compose.yml</code></pre>

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
      PMA_USER: root
      PMA_PASSWORD: root_password
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
    depends_on:
      - db
    networks:
      - fsnet

  frontend:
    image: 192.168.20.106:5000/frontend_tag:latest
    container_name: frontend
    ports:
      - "3000:3000"
    environment:
      NITRO_PORT: 3000
      NUXT_PUBLIC_API_BASE: "http://192.168.20.109:7000"
    networks:
      - fsnet

volumes:
  db_data:
  node_red_data:

networks:
  fsnet:
    driver: bridge</code></pre>
</div>

<hr style="margin: 40px 0;">

<h2>5. ตั้งค่า GitLab CI Variable</h2>
<p>เราต้องนำ Private Key ไปเก็บไว้ใน GitLab เพื่อให้ Pipeline เรียกใช้ตอน Deploy</p>
<ol>
    <li>บน Host รันคำสั่ง:
        <pre><code>docker exec gitlab-runner cat /home/gitlab-runner/.ssh/id_rsa</code></pre>
    </li>
    <li>Copy ทั้งก้อน (รวม BEGIN/END)</li>
    <li>ไปที่ GitLab Project -> <strong>Settings -> CI/CD -> Variables</strong></li>
    <li>Add variable:
        <ul>
            <li><strong>Key:</strong> <code>SSH_PRIVATE_KEY</code></li>
            <li><strong>Value:</strong> (วาง Private Key)</li>
            <li>✅ Mask variable</li>
            <li>✅ Protect</li>
        </ul>
    </li>
</ol>

<hr style="margin: 40px 0;">

<h2>6. ไฟล์ .gitlab-ci.yml (Full Pipeline)</h2>

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
# Test Docker
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

# ========================
# Build & Push
# ========================
build_push:
  stage: build_push
  image: docker:29
  tags:
    - docker
  script:
    - echo "Building images..."
    - docker build -t "$IMAGE_API:$CI_COMMIT_SHORT_SHA" backend
    - docker build -t "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA" frontend
    - docker tag "$IMAGE_API:$CI_COMMIT_SHORT_SHA" "$IMAGE_API:latest"
    - docker tag "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA" "$IMAGE_FRONT:latest"
    - docker push "$IMAGE_API:$CI_COMMIT_SHORT_SHA"
    - docker push "$IMAGE_FRONT:$CI_COMMIT_SHORT_SHA"
    - docker push "$IMAGE_API:latest"
    - docker push "$IMAGE_FRONT:latest"
  only:
    - main
  allow_failure: false

# ========================
# Deploy
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
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_ed25519
    - chmod 600 ~/.ssh/id_ed25519
    - echo "Adding host key..."
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
    - main
  allow_failure: false</code></pre>
</div>

<h3>Frontend Dockerfile (แก้ปัญหา Permission)</h3>
<p>ในโฟลเดอร์ <code>frontend/Dockerfile</code>:</p>
<div class="code-container">
<pre><code class="language-dockerfile">FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci || npm install

COPY . .

ARG NUXT_PUBLIC_API_BASE=http://192.168.20.109:7000
ENV NUXT_PUBLIC_API_BASE=${NUXT_PUBLIC_API_BASE}

# ----------------------------------------
# ➡️ แก้ปัญหา sh: nuxt: Permission denied
# ----------------------------------------
RUN chmod +x node_modules/.bin/nuxt || true
RUN chmod -R +x node_modules/.bin || true

RUN npm run build

# Production image
FROM node:20-alpine
WORKDIR /app
COPY --from=build /app/.output ./.output
COPY --from=build /app/package.json ./package.json
RUN npm install --omit=dev
EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]</code></pre>
</div>