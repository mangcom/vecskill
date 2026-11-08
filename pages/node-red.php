<h1>การติดตั้งและใช้งาน Node-RED (และการเลือก MQTT Broker)</h1>
<p>Node-RED คือเครื่องมือเขียนโปรแกรมแบบ Flow-based ที่ทำให้เราสามารถเชื่อมต่ออุปกรณ์ Hardware (เช่น ESP32), APIs, และบริการออนไลน์ต่างๆ เข้าด้วยกันได้ง่ายๆ ผ่านหน้าเว็บ</p>
<p>
  ก่อนจะติดตั้ง Node-RED เราจำเป็นต้องมี "ตลาดกลาง" สำหรับรับส่งข้อมูล IoT ก่อน ซึ่งเรียกว่า **MQTT Broker** ในคู่มือนี้ เราจะเปรียบเทียบและติดตั้ง Broker สองตัวที่นิยมที่สุดคือ **Mosquitto** และ
  **EMQX**
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
    <strong>หมายเหตุเรื่องคะแนน:</strong> ตามเกณฑ์ทักษะ ส่วน MQTT/Node-RED/Hardware/Dashboard นี้เป็นส่วนที่ <strong>มีคะแนนแบบมีเงื่อนไข</strong> ขึ้นอยู่กับว่าสนามแข่งขันนั้นๆ จัดแบบ "มี IoT" หรือ
    "ไม่มี IoT" (ถ้ามี IoT จะได้คะแนนส่วนนี้ 10 คะแนน แต่คะแนนส่วน Network จะลดลงเล็กน้อยเพื่อถัวเฉลี่ย) ให้ตรวจสอบประกาศของคณะกรรมการจัดการแข่งขันในปีและสนามของคุณว่าใช้รูปแบบไหน
    ก่อนเตรียมตัวในส่วนนี้
</p>

<hr style="margin: 40px 0" />

<h2>1. เลือกและติดตั้ง MQTT Broker (Mosquitto vs EMQX)</h2>
<p>คุณจำเป็นต้องเลือกติดตั้ง Broker **เพียง 1 ตัว** เท่านั้น เพื่อใช้เป็นศูนย์กลาง MQTT ของคุณ</p>

<h3>เปรียบเทียบ: Mosquitto vs EMQX</h3>
<table class="spec-table">
  <thead>
    <tr>
      <th>คุณสมบัติ</th>
      <th>Mosquitto (Eclipse)</th>
      <th>EMQX</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>ขนาดและการใช้งาน</strong></td>
      <td>เบามาก (Lightweight) เหมาะสำหรับโปรเจกต์ขนาดเล็กถึงกลาง หรือเครื่องที่มีทรัพยากรจำกัด</td>
      <td>ทรงพลัง (Powerful) ออกแบบมาสำหรับระบบขนาดใหญ่ (Massive Scale) และการทำ Clustering</td>
    </tr>
    <tr>
      <td><strong>ทรัพยากร (RAM/CPU)</strong></td>
      <td>ต่ำมาก</td>
      <td>ปานกลางถึงสูง</td>
    </tr>
    <tr>
      <td><strong>การจัดการ (Management)</strong></td>
      <td>ผ่านไฟล์คอนฟิก (`mosquitto.conf`) และ Terminal เท่านั้น</td>
      <td>ผ่านไฟล์คอนฟิก และมี **Web UI Dashboard** ในตัวที่ยอดเยี่ยม</td>
    </tr>
    <tr>
      <td><strong>Web UI ในตัว</strong></td>
      <td><strong style="color: #c0392b">ไม่มี</strong></td>
      <td><strong style="color: #28a745">มี</strong> (นี่คือจุดเด่นหลัก)</td>
    </tr>
    <tr>
      <td><strong>แนะนำสำหรับ</strong></td>
      <td>การเริ่มต้น, การทดสอบ, Homelab, หรือเมื่อต้องการความเรียบง่ายและประหยัดทรัพยากร</td>
      <td>การใช้งานจริงจัง, ต้องการ Monitoring แบบ Real-time, หรือต้องการ UI สำหรับจัดการ</td>
    </tr>
  </tbody>
</table>

<hr style="margin: 20px 0" />

<h3>1.1 ตัวเลือก A: Mosquitto (เบาและง่าย)</h3>
<p>หากคุณต้องการ Broker ที่เล็กและทำงานได้ดี ให้ใช้ตัวนี้</p>
<ol>
  <li>
    <strong>สร้าง Volumes:</strong>
    <pre><code>docker volume create mosquitto_config
docker volume create mosquitto_data
docker volume create mosquitto_log</code></pre>
  </li>
  <li>
    <strong>สร้าง Stack (`mosquitto_stack`):</strong>
    <pre><code>networks:
  app_net:
    external: true

volumes:
  mosquitto_config:
    external: true
  mosquitto_data:
    external: true
  mosquitto_log:
    external: true

services:
  mosquitto:
    image: eclipse-mosquitto:latest
    container_name: mosquitto
    restart: unless-stopped
    ports:
      - "1883:1883"  # MQTT Port
      - "9001:9001"  # MQTT over WebSockets
    volumes:
      - mosquitto_config:/mosquitto/config
      - mosquitto_data:/mosquitto/data
      - mosquitto_log:/mosquitto/log
    networks:
      - app_net
</code></pre>
  </li>
</ol>

<hr style="margin: 20px 0" />

<h3>1.2 ตัวเลือก B: EMQX (ทรงพลังและมี UI Dashboard)</h3>
<p>หากคุณต้องการ Broker ที่มี Dashboard สวยงามสำหรับดูสถานะ, จัดการ Client, และทดสอบการเชื่อมต่อ ให้ใช้ตัวนี้</p>
<ol>
  <li>
    <strong>สร้าง Volumes:</strong>
    <pre><code>docker volume create emqx_data
docker volume create emqx_log</code></pre>
  </li>
  <li>
    <strong>สร้าง Stack (`emqx_stack`):</strong>
    <pre><code>networks:
  app_net:
    external: true

volumes:
  emqx_data:
    external: true
  emqx_log:
    external: true

services:
  emqx:
    image: emqx/emqx:latest
    container_name: emqx
    restart: unless-stopped
    ports:
      - "1883:1883"     # MQTT Port
      - "8083:8083"     # MQTT over WebSockets
      - "18083:18083"   # Web UI Dashboard (สำคัญ!)
    volumes:
      - emqx_data:/opt/emqx/data
      - emqx_log:/opt/emqx/log
    networks:
      - app_net
</code></pre>
  </li>
</ol>

<h3>1.3 (EMQX) การใช้งาน UI Dashboard เบื้องต้น</h3>
<p>หลังจาก Deploy Stack EMQX สำเร็จ:</p>
<ol>
  <li>
    <strong>เข้าสู่หน้า Dashboard:</strong><br />
    เปิดเบราว์เซอร์ไปที่ <code>http://[IP_SERVER_ของคุณ]:18083</code>
  </li>
  <li>
    <strong>ล็อกอิน:</strong><br />
    ใช้ข้อมูลเริ่มต้น (Default):
    <ul>
      <li><strong>Username:</strong> <code>admin</code></li>
      <li><strong>Password:</strong> <code>public</code></li>
    </ul>
    <p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 10px">
        <strong>ควรเปลี่ยนรหัสผ่านทันที:</strong> <code>admin</code>/<code>public</code> เป็นรหัสผ่านเริ่มต้นที่รู้กันทั่วไป ควรเข้าไปเปลี่ยนที่เมนู Users หลังล็อกอินครั้งแรกเสมอ
    </p>
    <div class="image-container">
      <p>หน้าล็อกอินของ EMQX Dashboard</p>
    </div>
  </li>
  <li>
    <strong>สำรวจ Dashboard:</strong><br />
    คุณจะเห็นหน้าจอหลักที่แสดงสถานะการเชื่อมต่อ, จำนวน Topics, และข้อความที่วิ่งในระบบแบบ Real-time
    <div class="image-container">
      <p>หน้า Dashboard หลัก แสดงภาพรวมของ Broker</p>
    </div>
  </li>
  <li>
    <strong>ทดสอบการเชื่อมต่อ (WebSocket Tool):</strong><br />
    EMQX มีเครื่องมือทดสอบ MQTT Client ในตัว:
    <ul>
      <li>ไปที่เมนู <strong>Diagnosis</strong> > <strong>WebSocket Client</strong></li>
      <li>คลิก <strong>Connect</strong></li>
      <li>ในส่วน "Subscription" ให้พิมพ์ Topic (เช่น <code>vecskill</code>) แล้วคลิก <strong>Subscribe</strong></li>
      <li>ในส่วน "Publish" ให้พิมพ์ Topic (<code>vecskill</code>) และ Payload (<code>{"test": "hello"}</code>) แล้วคลิก <strong>Publish</strong></li>
    </ul>
    คุณจะเห็นข้อความที่คุณเพิ่ง Publish ปรากฏในกล่อง "Messages" ทันที
    <div class="image-container">
      <p>เครื่องมือ WebSocket Client ในตัวสำหรับทดสอบ Publish/Subscribe</p>
    </div>
  </li>
</ol>

<hr style="margin: 40px 0" />

<h2>2. การติดตั้ง Node-RED</h2>
<p>หลังจากคุณเลือกและติดตั้ง MQTT Broker (Mosquitto หรือ EMQX) เรียบร้อยแล้ว ขั้นต่อไปคือการติดตั้ง Node-RED</p>
<h3>2.1 สร้าง Volume</h3>
<pre><code>docker volume create nodered_data</code></pre>
<h3>2.2 สร้าง Stack (`nodered_stack`)</h3>
<pre><code>networks:
  app_net:
    external: true

volumes:
  nodered_data:
    external: true

services:
  node-red:
    image: nodered/node-red:latest
    container_name: node-red
    restart: unless-stopped
    environment:
      - TZ=Asia/Bangkok
    ports:
      - "1880:1880" # Web Admin UI
    volumes:
      - nodered_data:/data
    networks:
      - app_net
</code></pre>

<hr style="margin: 40px 0" />

<h2>3. การใช้งานเบื้องต้น (เชื่อมต่อ Node-RED กับ Broker)</h2>
<p>ทำตามขั้นตอนเหล่านี้เพื่อรับข้อมูลจาก ESP32 (ที่ส่งมาที่ Topic <code>vecskill</code>)</p>
<ol>
  <li><strong>เข้าสู่หน้า Node-RED:</strong> <code>http://[IP_SERVER_ของคุณ]:1880</code></li>
  <li>
    <strong>ลาก Node `mqtt in` มาวาง</strong>
  </li>
  <li>
    <strong>ตั้งค่า `mqtt in`:</strong>
    <ul>
      <li>
        <strong>Server:</strong> คลิกไอคอนดินสอเพื่อ "Add new mqtt-broker"
        <ul>
          <li><strong>Server:</strong> พิมพ์ <code>mosquitto</code> หรือ <code>emqx</code> (ขึ้นอยู่กับว่าคุณติดตั้งตัวไหน)</li>
          <li><strong>Port:</strong> <code>1883</code></li>
          <li>คลิก "Add"</li>
        </ul>
      </li>
      <li><strong>Topic:</strong> พิมพ์ <code>vecskill</code></li>
      <li><strong>Output:</strong> เลือก <strong>"a parsed JSON object"</strong></li>
      <li>คลิก "Done"</li>
    </ul>
    <div class="image-container">
      <p>การตั้งค่า Node `mqtt in` ให้เชื่อมต่อกับ Broker (mosquitto หรือ emqx)</p>
    </div>
  </li>
  <li><strong>ลาก Node `debug` มาวาง</strong> และเชื่อมต่อเส้นจาก `mqtt in`</li>
  <li><strong>Deploy</strong> และตรวจสอบหน้าต่าง Debug คุณจะเห็นข้อมูล JSON จาก ESP32</li>
</ol>

<hr style="margin: 40px 0" />

<h2>4. การตั้งค่าความปลอดภัย (เปิดใช้งาน Login)</h2>
<p>(ขั้นตอนนี้เหมือนเดิม) เราจะตั้งค่าให้ Node-RED มีระบบล็อกอิน</p>
<ol>
  <li><strong>เข้า Shell ของ `node-red` Container</strong> (ผ่าน Portainer หรือ <code>docker exec -it node-red bash</code>)</li>
  <li>
    <strong>ติดตั้ง `node-red-admin`:</strong>
    <pre><code>npm install -g node-red-admin</code></pre>
  </li>
  <li>
    <strong>สร้างรหัสผ่าน Hash:</strong> (ป้อนรหัสผ่านที่คุณต้องการ)
    <pre><code>node-red-admin hash-pw</code></pre>
    คัดลอกค่า Hash ที่ได้ (เช่น <code>$2b$08$...</code>)
  </li>
  <li>
    <strong>แก้ไขไฟล์ `settings.js`:</strong>
    <pre><code>nano /data/settings.js</code></pre>
  </li>
  <li>
    <strong>เปิดใช้งาน `adminAuth`:</strong><br />
    ค้นหา <code>//adminAuth: { ...</code> แล้วลบเครื่องหมาย <code>//</code> ออก และแก้ไข Username/Password:
    <pre><code class="language-javascript">adminAuth: {
    type: "credentials",
    users: [{
        username: "admin",
        password: "[HASHED_PASSWORD_HERE]", // วางค่า Hash ที่คัดลอกมา
        permissions: "*"
    }]
},
</code></pre>
  </li>
  <li><strong>รีสตาร์ท Container:</strong> <code>exit</code> ออกจาก Shell แล้วรีสตาร์ท Container `node-red` ผ่าน Portainer</li>
</ol>
<p>เมื่อรีเฟรชหน้า <code>:1880</code> อีกครั้ง คุณจะพบกับหน้าล็อกอิน</p>

<hr style="margin: 40px 0" />

<h2>5. ตัวอย่าง Docker Compose แบบรวม Stack เดียว (EMQX + Node-RED + Database + phpMyAdmin)</h2>
<p>
  ถ้าเกณฑ์การแข่งขันในสนามของคุณต้องการให้ <strong>บันทึกข้อมูลเซนเซอร์จาก IoT ลงฐานข้อมูล</strong> ไม่ใช่แค่ดูผ่าน Debug Node เฉยๆ
  การรวม EMQX + Node-RED + MySQL + phpMyAdmin ไว้ใน Stack เดียวกันจะช่วยให้ตั้งค่าเชื่อมต่อกันได้ง่ายกว่า (ใช้ชื่อ Service เป็น Hostname เรียกหากันได้ทันที)
</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>นี่คือ "ทางเลือก" ไม่ใช่ขั้นตอนเพิ่มเติม:</strong> ถ้าคุณเลือก Deploy Stack นี้ <strong>ไม่ต้อง</strong> สร้าง <code>emqx_stack</code> (ข้อ 1.2) และ <code>nodered_stack</code> (ข้อ 2) แยกต่างหากอีก เพราะ
  Stack นี้มี EMQX และ Node-RED รวมอยู่แล้ว การ Deploy ซ้ำซ้อนจะทำให้ Port ชนกัน (<code>1883</code>, <code>8083</code>, <code>18083</code>, <code>1880</code> ถูกจองไปแล้วโดย Stack เดิม)
</p>

<h3>5.1 วิเคราะห์และปรับปรุงจากตัวอย่างต้นฉบับ</h3>
<p>จากตัวอย่าง Compose ที่ส่งมา มีจุดที่ต้องปรับให้สอดคล้องกับ Service อื่นๆ ที่ตั้งค่าไว้ในคู่มือนี้ และแก้ไขข้อผิดพลาด ดังนี้:</p>
<table class="spec-table">
  <thead>
    <tr>
      <th>จุดที่ปรับ</th>
      <th>รายละเอียด</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Port 8080 ของ phpMyAdmin ชนกับ Registry UI</strong></td>
      <td>
        ถ้าใช้ VM สำเร็จรูปแบบ All-in-one (ทุก Service อยู่เครื่องเดียวกันตามหัวข้อ Quick Start) <code>8080</code> ถูกใช้โดย
        <strong>Registry UI</strong> (ดูหัวข้อ "การติดตั้ง Registry & UI") อยู่แล้ว ถ้า Deploy Stack นี้ซ้ำจะ Error "port is already allocated"
        จึงเปลี่ยนมาใช้ <strong><code>8081</code></strong> แทน
      </td>
    </tr>
    <tr>
      <td><strong>ชื่อ Container <code>db</code> / <code>phpmyadmin</code> ซ้ำกับ Stack อื่น</strong></td>
      <td>
        ชื่อนี้ถูกใช้ไปแล้วในตัวอย่าง Pipeline (<code>cicd-example-1.php</code>, <code>cicd-example-2.php</code>) ถ้า Deploy อยู่บนเครื่องเดียวกัน Docker
        จะ Error "The container name is already in use" จึงเปลี่ยนเป็น <strong><code>db-iot</code></strong> และ <strong><code>phpmyadmin-iot</code></strong> เพื่อให้สื่อความหมายและไม่ชนกัน
      </td>
    </tr>
    <tr>
      <td><strong>ชื่อ Volume <code>db_data</code> ซ้ำ</strong></td>
      <td>
        เช่นเดียวกับชื่อ Container ชื่อ Volume <code>db_data</code> ถูกใช้แล้วในตัวอย่าง Pipeline จึงเปลี่ยนเป็น <strong><code>iot_db_data</code></strong>
      </td>
    </tr>
    <tr>
      <td><strong>Bug: รหัสผ่านใน Healthcheck ไม่ตรงกัน</strong></td>
      <td>
        ต้นฉบับตั้ง <code>MYSQL_ROOT_PASSWORD: rootpassword</code> (ไม่มีขีดล่าง) แต่ Healthcheck เช็คด้วย <code>-proot_password</code> (มีขีดล่าง)
        ทำให้ <code>mysqladmin ping</code> ล็อกอินไม่ผ่านและ Container จะค้างสถานะ "unhealthy" ตลอด ได้แก้ให้ตรงกันแล้ว
      </td>
    </tr>
    <tr>
      <td><strong>เปิด Port 3306 ออกสู่ Host โดยไม่จำเป็น</strong></td>
      <td>
        ทั้ง phpMyAdmin และ Node-RED เชื่อมต่อ MySQL ผ่าน Docker Network ภายใน (เรียกด้วยชื่อ Service) ได้อยู่แล้ว ไม่จำเป็นต้องเปิดพอร์ต 3306
        ออกสู่ Host/อินเทอร์เน็ต การเปิดทิ้งไว้เป็นความเสี่ยงด้านความปลอดภัยโดยไม่มีประโยชน์เพิ่ม จึงเอาออก (ถ้าต้องการต่อด้วยโปรแกรมอย่าง MySQL Workbench
        จากเครื่อง Dev จริงๆ ค่อยเปิดเฉพาะตอนใช้งานแล้วปิดทันทีหลังใช้เสร็จ)
      </td>
    </tr>
    <tr>
      <td><strong>แยก Network เป็น <code>iot_net</code></strong></td>
      <td>
        เดิมทุก Service ใช้ <code>app_net</code> เส้นเดียว (Network กลางที่ Caddy ใช้ Reverse Proxy ไปหา Service อื่นๆ ทั้งหมดในคู่มือนี้) แนะนำให้แยกฐานข้อมูลออกมาอยู่ใน
        Network ใหม่ชื่อ <code>iot_net</code> ตามหลัก <strong>Network Segmentation</strong> คือให้เฉพาะ Service ที่จำเป็นต้องคุยกับฐานข้อมูลเท่านั้นที่เข้าถึงได้
        (<code>db-iot</code>, <code>phpmyadmin-iot</code>, และ <code>node-red</code>) ส่วน Service อื่นที่อยู่บน <code>app_net</code> เพียงอย่างเดียว (เช่น GitLab, Registry, Portainer)
        จะเข้าถึงฐานข้อมูลนี้ไม่ได้โดยตรง ลดผลกระทบถ้า Service ใด Service หนึ่งถูกเจาะ (Defense in Depth)<br /><br />
        <code>node-red</code> และ <code>emqx</code> ยังคงอยู่บน <code>app_net</code> ด้วย เพื่อให้ Caddy/NPM Reverse Proxy เข้าถึง Web UI ของทั้งคู่ได้เหมือนใน Stack แยกเดิม
        (ข้อ 1.2 และ 2) ส่วน <code>node-red</code> จะอยู่ทั้ง 2 Network เพราะต้องคุยกับทั้ง EMQX (ผ่าน <code>app_net</code>) และฐานข้อมูล (ผ่าน <code>iot_net</code>)
      </td>
    </tr>
  </tbody>
</table>

<h3>5.2 เตรียม Network และ Volumes</h3>
<pre><code>docker network create iot_net   # หากยังไม่เคยสร้าง (app_net ควรมีอยู่แล้วจากขั้นตอนก่อนหน้าในคู่มือนี้)

docker volume create iot_db_data
docker volume create emqx_data
docker volume create emqx_log
docker volume create nodered_data</code></pre>

<h3>5.3 สร้าง Stack (`iot_stack`)</h3>
<pre><code class="language-yaml">networks:
  app_net:
    external: true
  iot_net:
    external: true

volumes:
  iot_db_data:
    external: true
  emqx_data:
    external: true
  emqx_log:
    external: true
  nodered_data:
    external: true

services:
  db-iot:
    image: mysql:8.0
    container_name: db-iot
    restart: unless-stopped
    command: >
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: iot_db
      MYSQL_USER: iot_user
      MYSQL_PASSWORD: iot_password
      TZ: Asia/Bangkok
    volumes:
      - iot_db_data:/var/lib/mysql
      - ./schema.sql:/docker-entrypoint-initdb.d/schema.sql:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-prootpassword"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks:
      - iot_net   # ไม่เปิด ports ออกสู่ host เพราะเข้าถึงผ่าน network ภายในอยู่แล้ว

  phpmyadmin-iot:
    image: phpmyadmin:latest
    container_name: phpmyadmin-iot
    restart: unless-stopped
    environment:
      PMA_HOST: db-iot
      PMA_USER: root
      PMA_PASSWORD: rootpassword
    ports:
      - "8081:80"   # เปลี่ยนจาก 8080 เป็น 8081 เพื่อไม่ให้ชนกับ Registry UI
    depends_on:
      db-iot:
        condition: service_healthy
    networks:
      - iot_net

  node-red:
    image: nodered/node-red:latest
    container_name: node-red
    restart: unless-stopped
    environment:
      - TZ=Asia/Bangkok
    ports:
      - "1880:1880"   # Web Admin UI
    volumes:
      - nodered_data:/data
    depends_on:
      db-iot:
        condition: service_healthy
    networks:
      - app_net   # ให้ Caddy/NPM reverse proxy เข้าถึง Web UI ได้ และคุยกับ emqx
      - iot_net   # ให้เชื่อมต่อฐานข้อมูล db-iot ได้โดยตรง

  emqx:
    image: emqx/emqx:latest
    container_name: emqx
    restart: unless-stopped
    ports:
      - "1883:1883"     # MQTT Port
      - "8083:8083"     # MQTT over WebSockets
      - "18083:18083"   # Web UI Dashboard (สำคัญ!)
    volumes:
      - emqx_data:/opt/emqx/data
      - emqx_log:/opt/emqx/log
    networks:
      - app_net
</code></pre>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>หมายเหตุ:</strong> ไฟล์ <code>./schema.sql</code> เป็น Path บนเครื่อง Host ที่ต้องเตรียมไว้ก่อน Deploy (ใส่คำสั่ง SQL สร้างตารางที่ต้องการให้รันอัตโนมัติตอน MySQL
  เริ่มทำงานครั้งแรก) ถ้ายังไม่มีไฟล์นี้ ให้ลบบรรทัดนั้นออกจาก <code>volumes</code> ของ <code>db-iot</code> ก่อน แล้วค่อยสร้างตารางเองผ่าน phpMyAdmin ภายหลังก็ได้
</p>
<p>
  หลัง Deploy สำเร็จ เข้าใช้งาน phpMyAdmin ได้ที่ <code>http://[IP_SERVER_ของคุณ]:8081</code> (Username: <code>root</code>, Password: <code>rootpassword</code>)
  จากนั้นในฝั่ง Node-RED สามารถติดตั้ง Node เสริมอย่าง <code>node-red-node-mysql</code> จากเมนู <strong>Manage Palette</strong> เพื่อเพิ่ม Node สำหรับ Insert/Query
  ข้อมูลเซนเซอร์ที่รับมาจาก MQTT ลงตาราง MySQL (Host: <code>db-iot</code>, Database: <code>iot_db</code>, User: <code>iot_user</code>) ได้ทันที
</p>
