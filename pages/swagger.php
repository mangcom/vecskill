<h1>การติดตั้งและใช้งาน Swagger (OpenAPI) แบบ Offline</h1>
<p>
  Swagger คือชุดเครื่องมือสำหรับออกแบบและแสดงเอกสาร RESTful API ตามมาตรฐาน <strong>OpenAPI Specification</strong> ทำให้ API ของเรามีหน้าเอกสารสวยงาม ทดสอบยิง Request ได้จริงผ่านหน้าเว็บ
  (Try it out) และอ่านเข้าใจง่ายทั้งสำหรับทีมพัฒนาและกรรมการตรวจ
</p>
<p style="background-color: #d1ecf1; border-left: 5px solid #0c5460; padding: 15px">
  <strong>เกี่ยวข้องกับเกณฑ์การแข่งขัน:</strong> เกณฑ์ข้อ "การออกแบบ RESTful API สำหรับใช้งานในระบบ (Design / README.md)" ต้องการให้ตั้งชื่อ Endpoint สื่อความหมาย (เช่น
  <code>/api/teachers</code>, <code>/api/scores/{id}</code>), มีตัวอย่าง Response Body, และรองรับ Error Code (401, 404) อย่างเหมาะสม — Swagger คือเครื่องมือที่ตอบโจทย์ข้อนี้โดยตรง
  เพราะสร้างเอกสารที่ครบทุกหัวข้อนี้ได้ในที่เดียว
</p>
<p style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px">
  <strong>ทำงาน Offline ได้ 100%:</strong> วิธีที่แนะนำในหน้านี้ (<code>swagger-ui-express</code>) ดึงไฟล์ CSS/JS ของหน้า UI มาจาก Package <code>swagger-ui-dist</code> ที่ติดตั้งผ่าน <code>npm</code>
  โดยตรง <strong>ไม่ได้โหลดจาก CDN</strong> เมื่อ <code>npm install</code> เสร็จแล้วก็ใช้งานได้โดยไม่ต้องพึ่งอินเทอร์เน็ตอีกเลย
</p>

<hr style="margin: 40px 0" />

<h2>วิธีที่ 1 (แนะนำ): ฝัง Swagger UI เข้าไปใน Node.js/Express Backend</h2>
<p>วิธีนี้เหมาะที่สุดสำหรับโปรเจกต์ที่พัฒนาในการแข่งขัน เพราะเอกสาร API จะมาพร้อมกับ Backend ของคุณเองในที่เดียว</p>

<h3>1.1 ติดตั้ง Package ที่จำเป็น</h3>
<pre><code># เข้าไปที่โฟลเดอร์โปรเจกต์ Backend ของคุณ
npm install swagger-ui-express swagger-jsdoc</code></pre>
<ul>
  <li><strong>swagger-ui-express:</strong> สร้างหน้าเว็บ UI สำหรับแสดงเอกสาร (รวม Try it out)</li>
  <li><strong>swagger-jsdoc:</strong> แปลง Comment แบบ JSDoc ในโค้ดของเราให้กลายเป็นไฟล์ OpenAPI Spec อัตโนมัติ (ไม่ต้องเขียนไฟล์ YAML แยกให้เหนื่อย)</li>
</ul>

<h3>1.2 สร้างไฟล์ Config (`swagger.js`)</h3>
<pre><code class="language-javascript">// swagger.js
const swaggerJsdoc = require('swagger-jsdoc');

const options = {
  definition: {
    openapi: '3.0.0',
    info: {
      title: 'VECSkill API',
      version: '1.0.0',
      description: 'เอกสาร API สำหรับระบบที่พัฒนาในการแข่งขัน DevOps',
    },
    servers: [
      { url: 'http://localhost:3000', description: 'Development server' },
    ],
    components: {
      securitySchemes: {
        bearerAuth: {
          type: 'http',
          scheme: 'bearer',
          bearerFormat: 'JWT',
        },
      },
    },
  },
  // ไฟล์ที่ swagger-jsdoc จะไปอ่าน Comment (ปรับ Path ให้ตรงกับโครงสร้างโปรเจกต์ของคุณ)
  apis: ['./routes/*.js'],
};

module.exports = swaggerJsdoc(options);</code></pre>

<h3>1.3 เชื่อมเข้ากับ Express App หลัก</h3>
<pre><code class="language-javascript">// app.js หรือ index.js
const express = require('express');
const swaggerUi = require('swagger-ui-express');
const swaggerSpec = require('./swagger');

const app = express();

// เปิดเอกสารที่ /api-docs
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerSpec));

// ... route อื่นๆ ของคุณ ...

app.listen(3000, () => {
  console.log('Server running, API docs at http://localhost:3000/api-docs');
});</code></pre>

<h3>1.4 เขียน Comment บน Route เพื่อสร้างเอกสารอัตโนมัติ</h3>
<p>ตัวอย่างนี้ครบตามเกณฑ์: ตั้งชื่อ Endpoint สื่อความหมาย, มีตัวอย่าง Response, และรองรับ Error Code</p>
<pre><code class="language-javascript">// routes/teachers.js
const express = require('express');
const router = express.Router();

/**
 * @swagger
 * /api/teachers:
 *   get:
 *     summary: ดึงรายชื่อครูทั้งหมด
 *     tags: [Teachers]
 *     security:
 *       - bearerAuth: []
 *     responses:
 *       200:
 *         description: สำเร็จ ส่งคืนรายชื่อครู
 *         content:
 *           application/json:
 *             example:
 *               status: "success"
 *               data: [{ "id": 1, "name": "ครูไอที 01", "department": "IT" }]
 *       401:
 *         description: ไม่ได้ Login หรือ Token หมดอายุ
 *       404:
 *         description: ไม่พบข้อมูล
 */
router.get('/api/teachers', (req, res) => {
  // ... Logic ของคุณ ...
  res.json({ status: 'success', data: [] });
});

module.exports = router;</code></pre>
<p>รีสตาร์ท Server แล้วเปิด <code>http://localhost:3000/api-docs</code> จะเห็นหน้าเอกสารพร้อม Endpoint ที่เขียน Comment ไว้ขึ้นมาอัตโนมัติ และสามารถกดปุ่ม <strong>Try it out</strong> เพื่อทดสอบยิง Request จริงได้เลย</p>

<hr style="margin: 40px 0" />

<h2>วิธีที่ 2: Swagger UI แบบ Standalone ผ่าน Docker (แสดงไฟล์ Spec แยกจาก Backend)</h2>
<p>เหมาะสำหรับกรณีต้องการหน้าเอกสาร API แยกเป็น Service ของตัวเอง ไม่ต้องรวมกับ Backend (เช่น ให้กรรมการเข้าดูเอกสารได้โดยไม่ต้องรัน Backend)</p>

<h3>2.1 เตรียมไฟล์ Spec</h3>
<p>สร้างไฟล์ <code>openapi.yaml</code> เก็บไว้ในโฟลเดอร์ เช่น <code>/opt/swagger-docs/openapi.yaml</code> (เขียนเองหรือ Export มาจาก <code>swagger-jsdoc</code> ในวิธีที่ 1)</p>
<pre><code class="language-yaml">openapi: 3.0.0
info:
  title: VECSkill API
  version: 1.0.0
paths:
  /api/teachers:
    get:
      summary: ดึงรายชื่อครูทั้งหมด
      responses:
        '200':
          description: สำเร็จ
        '401':
          description: ไม่ได้ Login
</code></pre>

<h3>2.2 สร้าง Stack ผ่าน Portainer</h3>
<pre><code>services:
  swagger-ui:
    image: swaggerapi/swagger-ui:latest
    container_name: swagger-ui
    restart: unless-stopped
    ports:
      - "8085:8080"
    volumes:
      - /opt/swagger-docs/openapi.yaml:/usr/share/nginx/html/openapi.yaml:ro
    environment:
      - SWAGGER_JSON=/usr/share/nginx/html/openapi.yaml
    networks:
      - app_net

networks:
  app_net:
    external: true
</code></pre>
<p>Deploy แล้วเข้าที่ <code>http://[IP_SERVER]:8085</code> จะเห็นหน้าเอกสาร Swagger UI ที่อ่านจากไฟล์ <code>openapi.yaml</code> ที่เตรียมไว้</p>
<p style="font-size: 0.9em; color: #666">
  <em>หมายเหตุ: Image นี้ต้องดาวน์โหลดมาเก็บไว้ล่วงหน้าตอนยังมีอินเทอร์เน็ตเช่นเดียวกับ Image อื่นๆ (<code>docker pull swaggerapi/swagger-ui:latest</code>) แต่ตัวหน้าเว็บเองไม่ได้พึ่ง CDN ภายนอกเมื่อรันแล้ว</em>
</p>
<p>
  ต้องการทำ Reverse Proxy ให้เข้าผ่านโดเมนสวยๆ เช่น <code>https://api-docs.vecskill.ovec</code> ดูวิธีได้จากบท
  <a href="#" onclick="document.querySelector('a[data-page=\'caddy-proxy\']').click();">Caddy Reverse Proxy</a> หรือ
  <a href="#" onclick="document.querySelector('a[data-page=\'npm-proxy\']').click();">Nginx Proxy Manager</a>
</p>
