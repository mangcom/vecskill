<h1>ตัวอย่าง CI/CD Pipeline: Test, Build & Deploy</h1>
<p>หน้านี้รวบรวมตัวอย่าง Configuration สำหรับระบบ CI/CD ที่รวมขั้นตอนการทำ Unit Test, Docker Check, Build Image และ Deploy ไปยัง Production Server ตามที่คุณต้องการ</p>

<hr style="margin: 40px 0" />

<h2>1. Docker Compose (Production)</h2>
<p>ไฟล์ <code>docker-compose.yaml</code> สำหรับรันบนเครื่อง Production</p>

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
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-proot_password"]
      interval: 10s
      timeout: 5s
      retries: 10
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
    depends_on:
      - db
    networks:
      - fsnet

  nodered:
    image: nodered/node-red:4.0.2
    container_name: nodered
    ports:
      - "1801:1880"
    volumes:
      - nodered_data:/data
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
      NODE_ENV: production
      JWT_SECRET: testing123
      JWT_EXPIRES: 2h
      UPLOAD_DIR: uploads/documents
    depends_on:
      db:
        condition: service_started
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
    depends_on:
      - api
    networks:
      - fsnet

volumes:
  db_data:
    external: true
    name: skills_db_data

  nodered_data:
    external: true
    name: skills_nodered_data

networks:
  fsnet:
    driver: bridge</code></pre>

<hr style="margin: 40px 0" />

<h2>2. GitLab CI Configuration (เพิ่ม Test)</h2>

<h3>แนวคิดก่อนลงมือ</h3>
<ul>
  <li><strong>ลำดับ pipeline ใหม่:</strong></li>
  <li><code>backend_test</code> ← รัน npm test ในโฟลเดอร์ backend</li>
  <li><code>test-docker</code> ← เช็ค docker บน runner (จะให้วิ่งก่อน/หลัง test ก็ได้)</li>
  <li><code>build_push</code> ← ถ้า test ผ่าน ค่อย build & push image</li>
  <li><code>deploy</code> ← deploy ไป production</li>
</ul>

<h3>ไฟล์ .gitlab-ci.yml</h3>
<pre><code class="language-yaml">stages:
  - test          # ⬅ stage รัน test (ไม่บล็อก pipeline)
  - build_push
  - deploy

variables:
  REGISTRY_URL: "192.168.20.106:5000"
  IMAGE_API: "$REGISTRY_URL/backend_tag"
  IMAGE_FRONT: "$REGISTRY_URL/frontend_tag"
  DOCKER_TLS_CERTDIR: ""

# ========================
# Backend Unit/Integration Tests (Vitest)
# ========================
backend_test:
  stage: test
  image: node:22-alpine          # ใช้ node image สำหรับรัน npm / vitest
  tags:
    - docker
  script:
    - echo "Run backend tests (Vitest)..."
    - cd backend
    - npm ci                      # ติดตั้งตาม package-lock.json
    - npm test                    # เท่ากับที่ครูรันในเครื่อง (vitest run)
  only:
    - main                        # ทดสอบเฉพาะ branch main
  allow_failure: true             # ⬅ ถ้า test ตก → pipeline ยังไปต่อได้

# ========================
# Test Docker (pre-check)
# ========================
test-docker:
  stage: test
  image: docker:29
  tags:
    - docker
  script:
    - echo "Testing Docker..."
    - docker info
  only:
    - main                        # ให้เช็ค docker เฉพาะ main
  allow_failure: true             # ⬅ ถ้า docker info error → pipeline ยังไปต่อได้

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
  allow_failure: false            # ⬅ ถ้า build/push พัง → pipeline พัง

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
  allow_failure: false            # ⬅ deploy พัง → pipeline พัง
</code></pre>
