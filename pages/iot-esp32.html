<h1>ตัวอย่าง IoT: ESP32 + BME/BMP280 + MQTT + Node-RED</h1>
<p>
  ในบทนี้ เราจะมาดูตัวอย่างการสร้างระบบ Internet of Things (IoT) อย่างง่าย โดยใช้บอร์ดไมโครคอนโทรลเลอร์ ESP32 อ่านค่าสภาพแวดล้อม (อุณหภูมิ, ความชื้น, ความดัน) จากเซ็นเซอร์ BME/BMP280
  แล้วส่งข้อมูลผ่านโปรโตคอล MQTT ไปยัง Node-RED เพื่อนำไปประมวลผลหรือบันทึกลงฐานข้อมูลต่อไป
</p>
<p>ตัวอย่างนี้จะแสดงให้เห็นถึงการทำงานร่วมกันของ Hardware (ESP32, Sensor), Network Protocol (MQTT), และ Software (Node-RED) ที่ทำงานอยู่บน Server ของเรา</p>

<hr style="margin: 40px 0" />

<h2>1. 📡 อุปกรณ์และ Library ที่ต้องใช้</h2>
<p>ก่อนเริ่มต้น คุณต้องเตรียมอุปกรณ์ Hardware และติดตั้ง Library ที่จำเป็นใน Arduino IDE ของคุณก่อน</p>
<h3>Hardware</h3>
<ul>
  <li>บอร์ด ESP32</li>
  <li>เซ็นเซอร์ BME280 หรือ BMP280 (I2C Module)</li>
  <li>สาย Jumper สำหรับเชื่อมต่อ</li>
</ul>
<h3>การเชื่อมต่อ (I2C)</h3>
<ul>
  <li><strong>VCC (Sensor)</strong> -> <strong>3.3V (ESP32)</strong></li>
  <li><strong>GND (Sensor)</strong> -> <strong>GND (ESP32)</strong></li>
  <li><strong>SDA (Sensor)</strong> -> <strong>GPIO 21 (ESP32 - SDA)</strong></li>
  <li><strong>SCL (Sensor)</strong> -> <strong>GPIO 22 (ESP32 - SCL)</strong></li>
</ul>
<div class="image-container">
  <img src="images/BMP280_ESP32.png" alt="Docker Hello World Output" class="content-image" />
  <p>ตัวอย่างการเชื่อมต่อ ESP32 กับ BME/BMP280 ผ่าน I2C</p>
  <p>ภาพตัวอย่างจาก https://www.electronicwings.com/esp32/bmp280-barometer-sensor-interfacing-with-esp32</p>
</div>
<p>ต้นฉบับจาก <a href="https://www.electronicwings.com/esp32/bmp280-barometer-sensor-interfacing-with-esp32" target="_blank"> ElectronicWings</a></p>
<h3>Arduino Libraries</h3>
<p>เปิด Arduino IDE แล้วไปที่ <strong>Sketch > Include Library > Manage Libraries...</strong> จากนั้นค้นหาและติดตั้ง Libraries ต่อไปนี้:</p>
<ul>
  <li><strong>Adafruit BME280 Library</strong> (Library นี้รองรับทั้ง BME280 และ BMP280)</li>
  <li><strong>Adafruit Unified Sensor</strong> (มักจะถูกติดตั้งพร้อมกับ BME280)</li>
  <li><strong>PubSubClient</strong> (สำหรับจัดการการเชื่อมต่อ MQTT)</li>
</ul>

<hr style="margin: 40px 0" />

<h2>2. 💻 Code ตัวอย่างสำหรับ ESP32 (Arduino)</h2>
<p>โค้ดนี้จะเชื่อมต่อ WiFi, อ่านค่าจากเซ็นเซอร์ BME/BMP280 ทุกๆ 10 วินาที, จัดรูปแบบข้อมูลเป็น JSON, และส่งไปยัง MQTT Broker (ที่คุณตั้งค่าไว้ใน Node-RED หรือติดตั้งแยกต่างหากบน Server)</p>
<p style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px">
  <strong>สำคัญ:</strong> แก้ไขค่า <code>ssid</code>, <code>password</code>, และ <code>mqtt_server</code> ในโค้ดให้ตรงกับการตั้งค่าของคุณ
</p>
<pre><code class="language-cpp">#include &lt;WiFi.h&gt;
#include &lt;PubSubClient.h&gt;
#include &lt;Wire.h&gt;
#include &lt;Adafruit_Sensor.h&gt;
#include &lt;Adafruit_BME280.h&gt;

// --- ตั้งค่า WiFi ---
const char* ssid = "YOUR_WIFI_SSID";       // ใส่ชื่อ WiFi ของคุณ
const char* password = "YOUR_WIFI_PASSWORD"; // ใส่รหัสผ่าน WiFi

// --- ตั้งค่า MQTT ---
const char* mqtt_server = "YOUR_NODE_RED_SERVER_IP"; // IP ของ Server ที่รัน MQTT Broker
const int mqtt_port = 1883;                    // Port มาตรฐานของ MQTT
const char* mqtt_topic = "vecskill";           // Topic ที่ต้องการส่งข้อมูลไป

// --- ตั้งค่า Sensor ---
Adafruit_BME280 bme; // ใช้งาน I2C
// หมายเหตุ: ถ้าใช้ BMP280, ค่าความชื้น (Humidity) จะอ่านไม่ได้

// --- ตัวแปรจัดการเวลา ---
unsigned long previousMillis = 0;
const long interval = 10000; // ส่งข้อมูลทุก 10 วินาที (10000 ms)

// --- Clients ---
WiFiClient espClient;
PubSubClient client(espClient);

/**
 * ฟังก์ชันเชื่อมต่อ WiFi
 */
void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.println("WiFi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

/**
 * ฟังก์ชันเชื่อมต่อ MQTT Broker
 */
void reconnect_mqtt() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    String clientId = "ESP32Client-";
    clientId += String(random(0xffff), HEX);

    if (client.connect(clientId.c_str())) {
      Serial.println("connected");
    } else {
      Serial.print("failed, rc=");
      Serial.print(client.state());
      Serial.println(" try again in 5 seconds");
      delay(5000); // รอ 5 วินาทีก่อนลองใหม่
    }
  }
}

/**
 * ฟังก์ชัน Setup หลัก
 */
void setup() {
  Serial.begin(115200);
  Wire.begin(); // เริ่มต้น I2C (SDA=21, SCL=22 สำหรับ ESP32)

  setup_wifi();
  client.setServer(mqtt_server, mqtt_port);

  Serial.println("Initializing BME/BMP280 sensor...");
  if (!bme.begin(0x76)) { // ตรวจสอบ Sensor ที่ I2C address 0x76 (หรือ 0x77)
    Serial.println("Could not find a valid BME/BMP280 sensor, check wiring!");
    while (1) delay(10);
  }
  Serial.println("Sensor found!");
}

/**
 * ฟังก์ชัน Loop หลัก
 */
void loop() {
  if (!client.connected()) {
    reconnect_mqtt();
  }
  client.loop(); // ให้ MQTT client ทำงาน

  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis >= interval) {
    previousMillis = currentMillis;

    // --- อ่านค่าจาก Sensor ---
    float temperature = bme.readTemperature();       // องศาเซลเซียส
    float humidity = bme.readHumidity();         // %RH
    float pressure = bme.readPressure() / 100.0F; // hPa

    if (isnan(temperature) || isnan(pressure)) {
      Serial.println("Failed to read from BME/BMP sensor!");
      return;
    }
    if (isnan(humidity)) {
      humidity = 0.0; // BMP280 ไม่มีเซ็นเซอร์ความชื้น
    }

    // --- สร้างข้อความ JSON ---
    char jsonPayload[256];
    snprintf(jsonPayload, sizeof(jsonPayload),
             "{\"temperature\":%.2f, \"humidity\":%.2f, \"pressure\":%.2f}",
             temperature, humidity, pressure);

    // --- ส่งข้อมูลไปยัง MQTT Broker ---
    Serial.print("Publishing to topic: ");
    Serial.println(mqtt_topic);
    Serial.println(jsonPayload);

    if (client.publish(mqtt_topic, jsonPayload)) {
      Serial.println("Message published successfully");
    } else {
      Serial.println("Message publish failed");
    }
  }
}</code></pre>
<p>ตัวอย่างที่ 2</p>
<pre><code>#define MQTT_VERSION MQTT_VERSION_3_1_1
#include &lt;WiFi.h&gt;
#include &lt;PubSubClient.h&gt;
#include &lt;Wire.h&gt;
#include &lt;Adafruit_Sensor.h&gt;
#include &lt;Adafruit_BME280.h&gt; // เปลี่ยนจาก BMP280 เป็น BME280
// --- ตั้งค่า WiFi ---
const char* ssid = "YOUR_WIFI_SSID";       // ใส่ชื่อ WiFi ของคุณ
const char* password = "YOUR_WIFI_PASSWORD"; // ใส่รหัสผ่าน WiFi
// --- ตั้งค่า MQTT ---
const char* mqtt_server = "YOUR_NODE_RED_SERVER_IP"; // IP ของ Server ที่รัน MQTT Broker
const int mqtt_port = 1883;                    // Port มาตรฐานของ MQTT
const char* mqtt_topic = "vecskill";           // Topic ที่ต้องการส่งข้อมูลไป
// --- ตั้งค่า Sensor ---
Adafruit_BME280 bme; // ใช้งาน I2C
// หมายเหตุ: ถ้าใช้ BMP280, ค่าความชื้น (Humidity) จะอ่านไม่ได้ 
// --- ตัวแปรจัดการเวลา ---
unsigned long previousMillis = 0;
const long interval = 10000; // ส่งข้อมูลทุก 10 วินาที (10000 ms)
// --- Clients ---
WiFiClient espClient;
PubSubClient client(espClient);
void setup() {
  Serial.begin(115200);
  Wire.begin(21, 22); // SDA = 21, SCL = 22

  Serial.println("\nI2C Scanner");
  for (uint8_t address = 1; address < 127; address++) {
    Wire.beginTransmission(address);
    if (Wire.endTransmission() == 0) {
      Serial.print(" Found I2C device at 0x");
      Serial.println(address, HEX);
    }
  }
  Serial.println("Scan done!");
  Serial.print("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\n WiFi connected");
  Serial.print("ESP32 IP: ");
  Serial.println(WiFi.localIP());

  //  เริ่มต้นเซนเซอร์ BME280
  if (!bme.begin(0x76)) {   // address ปกติคือ 0x76 หรือ 0x77
    Serial.println("Could not find BME280 sensor!");
    while (1);
  }
  Serial.println(" BME280 sensor initialized!");
  // ตั้งค่า MQTT
  client.setServer(mqtt_server, mqtt_port);

  while (!client.connected()) {
    Serial.print("Connecting to MQTT...");
    if (client.connect("ESP32_Client_01")) {
      Serial.println("connected!");
    } else {
      Serial.print("failed, rc=");
      Serial.print(client.state());
      Serial.println(" retrying in 2 seconds...");
      delay(2000);
    }
  }
}

void loop() {
  if (!client.connected()) {
    while (!client.connected()) {
      Serial.print("Reconnecting MQTT...");
      if (client.connect("ESP32_Client_01")) {
        Serial.println("connected!");
      } else {
        delay(2000);
      }
    }
  }

  client.loop();
  // อ่านค่าจาก BME280
  float temperature = bme.readTemperature(); // *c
  float humidity = bme.readHumidity(); // %RH
  float pressure = bme.readPressure() / 100.0F; // hPa 
  // สร้างข้อความ JSON ที่ถูกต้อง (ไม่มี , เกิน)
  char payload[128];
  snprintf(payload, sizeof(payload),
          "{\"temperature\": %.2f, \"humidity\": %.2f, \"pressure\": %.2f}",
           temperature, humidity, pressure);
  // แสดงใน Serial และส่งขึ้น MQTT
  Serial.print("Publish payload: ");
  Serial.println(payload);
  client.publish(mqtt_topic, payload);

  delay(2000); // ส่งทุก 2 วินาที
}

</code></pre>
<p>ตัวอย่างที่ 3</p>
<pre><code>// ====== ESP32 + BME280 + MQTT (Robust Reconnect) : commented line-by-line ======
#define MQTT_VERSION MQTT_VERSION_3_1_1           // กำหนดเวอร์ชันโปรโตคอล MQTT ที่ใช้กับ PubSubClient (3.1.1)

// ---------------------- ส่วนรวมไลบรารีที่ต้องใช้ ----------------------
#include &lt;WiFi.h&gt;                                 // ไลบรารี Wi-Fi สำหรับ ESP32
#include &lt;PubSubClient.h&gt;                         // ไลบรารี MQTT client (ใช้กับ broker เช่น Mosquitto/Node-RED)
#include &lt;Wire.h&gt;                                 // ไลบรารี I2C (สำหรับคุยกับ BME280)
#include &lt;Adafruit_Sensor.h&gt;                      // อินเทอร์เฟซเซนเซอร์แบบ Unified ของ Adafruit
#include &lt;Adafruit_BME280.h&gt;                      // ไดรเวอร์เซนเซอร์ BME280 (อุณหภูมิ/ความชื้น/ความดัน)

// ---------------------- การตั้งค่าพื้นฐาน (แก้ให้ตรงสภาพจริง) ----------------------
const char* WIFI_SSID     = "YOUR_WIFI_SSID";     // ชื่อเครือข่าย Wi-Fi ที่จะเชื่อมต่อ
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD"; // รหัสผ่านของ Wi-Fi

const char* MQTT_HOST     = "192.168.1.10";       // IP/โฮสต์ของ MQTT broker (เช่น เครื่อง Node-RED หรือ Mosquitto)
const uint16_t MQTT_PORT  = 1883;                 // พอร์ต MQTT (ปกติ 1883 สำหรับแบบไม่เข้ารหัส)

// ตั้งชื่อหัวข้อ (topic) ที่ใช้งาน
const char* TELE_TOPIC   = "vecskill/telemetry";  // หัวข้อสำหรับส่งค่าจากเซนเซอร์ (telemetry)
const char* STATUS_TOPIC = "vecskill/status";     // หัวข้อสำหรับประกาศสถานะอุปกรณ์ (online/offline)

// กำหนดรอบเวลา (มิลลิวินาที) สำหรับงานต่าง ๆ
const uint32_t WIFI_RETRY_MS = 10'000;            // เวลาห่างระหว่างความพยายามเชื่อม Wi-Fi ใหม่ (ทุก 10 วินาที)
const uint32_t MQTT_RETRY_MS = 5'000;             // เวลาห่างระหว่างความพยายามเชื่อม MQTT ใหม่ (ทุก 5 วินาที)
const uint32_t PUBLISH_EVERY = 2'000;             // ส่งข้อมูลเทเลเมทรีทุก ๆ 2 วินาที

// ที่อยู่ I2C ของ BME280 (ขึ้นกับบอร์ด/โมดูลที่ใช้)
#define BME280_I2C_ADDR 0x76                      // ค่ามาตรฐานที่พบได้บ่อยคือ 0x76 (บางรุ่นอาจเป็น 0x77)

// ---------------------- สร้างออบเจ็กต์หลัก ----------------------
WiFiClient      espClient;                        // ออบเจ็กต์ TCP client สำหรับให้ PubSubClient ใช้สื่อสารกับ broker
PubSubClient    mqtt(espClient);                  // ออบเจ็กต์ MQTT client
Adafruit_BME280 bme;                              // ออบเจ็กต์ BME280 สำหรับอ่านค่าจากเซนเซอร์

// ---------------------- ตัวแปรสถานะ/ตัวช่วยด้านเวลา ----------------------
unsigned long lastWifiAttempt = 0;                // จับเวลาครั้งล่าสุดที่พยายามเชื่อมต่อ Wi-Fi
unsigned long lastMqttAttempt = 0;                // จับเวลาครั้งล่าสุดที่พยายามเชื่อมต่อ MQTT
unsigned long lastPublish     = 0;                // จับเวลาครั้งล่าสุดที่ส่งข้อมูลเทเลเมทรี
bool          bmeReady        = false;            // ธงบอกว่า BME280 พร้อมใช้งานแล้วหรือยัง

// ---------------------- กำหนดขา LED สำหรับกะพริบยืนยันการส่ง ----------------------
#if defined(LED_BUILTIN)                           // ถ้ามีมาโคร LED_BUILTIN (บางบอร์ดกำหนดไว้)
const int LED_PIN = LED_BUILTIN;                  // ใช้ขา LED บนบอร์ด
#else
const int LED_PIN = 2;                            // ถ้าไม่มีก็ใช้ GPIO2 (บน ESP32 หลายบอร์ดต่อกับ LED ออนบอร์ด)
#endif

// ---------------------- ฟังก์ชันรับเหตุการณ์ Wi-Fi (event handler) ----------------------
void onWiFiEvent(WiFiEvent_t event) {             // ฟังก์ชันนี้จะถูกเรียกเมื่อมีเหตุการณ์ของ Wi-Fi เกิดขึ้น
  switch (event) {                                // ตรวจชนิดของเหตุการณ์
    case ARDUINO_EVENT_WIFI_STA_CONNECTED:        // เหตุการณ์: เชื่อมต่อกับจุดกระจายสัญญาณ (AP) สำเร็จ
      Serial.println(F("[WiFi] Connected to AP")); // แสดงข้อความใน Serial Monitor
      break;                                      // ออกจาก switch สำหรับเคสนี้

    case ARDUINO_EVENT_WIFI_STA_GOT_IP:           // เหตุการณ์: ได้รับ IP address จาก DHCP ของ AP แล้ว
      Serial.print(F("[WiFi] Got IP: "));         // พิมพ์ข้อความบอกว่าได้ IP แล้ว
      Serial.println(WiFi.localIP());             // พิมพ์ IP ที่ได้รับ
      break;                                      // ออกจาก switch

    case ARDUINO_EVENT_WIFI_STA_DISCONNECTED:     // เหตุการณ์: หลุดการเชื่อมต่อจาก AP
      Serial.print(F("[WiFi] Disconnected, reason=")); // พิมพ์ข้อความสาเหตุ
      Serial.println(WiFi.reasonCode());          // พิมพ์รหัสเหตุผลการหลุด (ดูปัญหาเบื้องต้นได้)
      if (mqtt.connected()) mqtt.disconnect();    // ถ้า MQTT ยังเชื่อมอยู่ให้ตัดก่อน เพื่อบังคับให้ต่อใหม่หลัง Wi-Fi กลับมา
      break;                                      // ออกจาก switch

    default:                                      // เหตุการณ์อื่น ๆ ที่ไม่ได้จัดการเฉพาะ
      break;                                      // ไม่ทำอะไรเพิ่มเติม
  }
}

// ---------------------- ฟังก์ชันดูแลการเชื่อมต่อ Wi-Fi (non-blocking) ----------------------
bool ensureWiFi() {                               // คืนค่า true ถ้าเชื่อม Wi-Fi อยู่แล้ว
  if (WiFi.status() == WL_CONNECTED) return true; // ถ้าเชื่อมต่อแล้วก็จบ (true)

  unsigned long now = millis();                   // เวลาปัจจุบันนับจากเปิดเครื่อง (มิลลิวินาที)
  if (now - lastWifiAttempt < WIFI_RETRY_MS)      // ถ้ายังไม่ถึงรอบเวลาที่ต้องพยายามใหม่
    return false;                                 // ออกจากฟังก์ชัน (false เพราะยังไม่เชื่อม)

  lastWifiAttempt = now;                          // บันทึกเวลาที่พยายามล่าสุดเป็นตอนนี้
  Serial.print(F("[WiFi] Connecting to "));       // พิมพ์ข้อความว่ากำลังเชื่อมต่อ
  Serial.print(WIFI_SSID);                        // พิมพ์ชื่อ SSID
  Serial.println(F(" ..."));                      // ปิดท้ายด้วยจุด ๆ

  WiFi.mode(WIFI_STA);                            // ตั้งโหมดเป็นสถานี (เชื่อมกับ AP)
  WiFi.setAutoReconnect(true);                    // ขอให้ core พยายาม reconnect อัตโนมัติ
  WiFi.persistent(false);                         // ไม่เขียนข้อมูล Wi-Fi ลงแฟลชทุกครั้ง (ยืดอายุแฟลช)
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);           // เริ่มกระบวนการเชื่อมต่อ (ไม่บล็อก loop)
  return false;                                   // ณ จุดนี้ยังไม่เชื่อม จึงคืน false (ลูปจะกลับมาเช็คอีก)
}

// ---------------------- ฟังก์ชันดูแลการเชื่อมต่อ MQTT (non-blocking) ----------------------
bool ensureMQTT() {                               // คืน true ถ้าเชื่อม MQTT แล้ว
  if (WiFi.status() != WL_CONNECTED) return false;// ถ้า Wi-Fi ยังไม่พร้อม ก็ยังเชื่อม MQTT ไม่ได้
  if (mqtt.connected()) return true;              // ถ้า MQTT เชื่อมแล้ว ก็จบ (true)

  unsigned long now = millis();                   // อ่านเวลา ณ ตอนนี้
  if (now - lastMqttAttempt < MQTT_RETRY_MS)      // ถ้ายังไม่ถึงรอบที่ต้องลองเชื่อมใหม่
    return false;                                 // ออกไปก่อน (false)

  lastMqttAttempt = now;                          // บันทึกเวลาพยายามล่าสุด
  mqtt.setServer(MQTT_HOST, MQTT_PORT);           // กำหนดที่อยู่และพอร์ตของ broker
  mqtt.setKeepAlive(30);                          // ตั้งค่า keep-alive เป็น 30 วินาที (broker จะคาดหวัง ping ภายในช่วงนี้)
  mqtt.setSocketTimeout(30);                      // ตั้ง timeout การอ่าน/เขียน socket 30 วินาที

  char clientId[40];                              // บัฟเฟอร์สำหรับเก็บ clientID (ต้องไม่ซ้ำใน broker)
  uint64_t mac = ESP.getEfuseMac();               // อ่านเลข MAC ของ ESP32 (ยูนีค)
  snprintf(clientId, sizeof(clientId),            // สร้าง clientID จาก MAC (เช่น ESP32_ABCDEF12)
           "ESP32_%llX", mac);

  Serial.print(F("[MQTT] Connecting as "));       // พิมพ์ว่ากำลังเชื่อม MQTT
  Serial.print(clientId);                         // พิมพ์ clientID
  Serial.print(F(" to "));                        // ข้อความต่อ
  Serial.print(MQTT_HOST);                        // พิมพ์โฮสต์ของ broker
  Serial.print(F(":"));                           // คั่นด้วย colon
  Serial.println(MQTT_PORT);                      // พิมพ์พอร์ตของ broker

  // เชื่อม MQTT พร้อมตั้งค่า Last Will & Testament (LWT)
  bool ok = mqtt.connect(                         // เรียกเชื่อมต่อไปยัง broker
    clientId,                                     // clientID ที่ยูนีค
    STATUS_TOPIC,                                 // willTopic: หัวข้อที่จะส่งให้เองถ้าคลายเชื่อมต่อแบบผิดปกติ
    1,                                            // willQoS: ใช้ QoS 1 สำหรับ LWT
    true,                                         // willRetain: เก็บ retain ให้ผู้มาทีหลังเห็นสถานะล่าสุด
    "offline"                                     // willMessage: ข้อความ LWT เมื่อหลุด
  );

  if (ok) {                                       // ถ้าเชื่อมต่อสำเร็จ
    Serial.println(F("[MQTT] Connected"));        // พิมพ์ใน Serial ว่าสำเร็จ
    mqtt.publish(STATUS_TOPIC, "online", true);   // ประกาศว่า online (retain=true) เพื่ออัปเดตสถานะ
    // (ถ้าต้องการ subscribe หัวข้อใด ๆ ให้เพิ่ม mqtt.subscribe(...) ที่นี่)
  } else {                                        // ถ้าเชื่อมไม่สำเร็จ
    Serial.print(F("[MQTT] Failed, rc="));        // พิมพ์รหัสความผิดพลาด
    Serial.println(mqtt.state());                 // mqtt.state() แสดงเหตุผล (เช่น -2, -4, ฯลฯ)
  }
  return ok;                                      // คืนค่าผลลัพธ์การเชื่อม
}

// ---------------------- ฟังก์ชันอ่าน BME280 และส่งขึ้น MQTT ----------------------
void publishTelemetry() {                         // ฟังก์ชันส่งข้อมูลเทเลเมทรี (JSON)
  if (!bmeReady || !mqtt.connected()) return;     // ถ้าเซนเซอร์ไม่พร้อม หรือ MQTT ยังไม่เชื่อม ให้ยกเลิก

  float t = bme.readTemperature();                // อ่านอุณหภูมิ (°C)
  float h = bme.readHumidity();                   // อ่านความชื้นสัมพัทธ์ (%RH)
  float p = bme.readPressure() / 100.0F;          // อ่านความดัน (Pa) แล้วแปลงเป็น hPa โดยหาร 100

  if (isnan(t) || isnan(h) || isnan(p)) {         // ป้องกันกรณีอ่านค่าได้ NaN (อ่านพลาด)
    Serial.println(F("[BME280] NAN reading, skip"));// แจ้งเตือนแล้วไม่ส่ง
    return;                                       // ออกจากฟังก์ชัน
  }

  char payload[160];                              // บัฟเฟอร์เก็บ JSON ที่จะส่ง
  snprintf(payload, sizeof(payload),              // สร้างสตริง JSON ด้วย snprintf
           "{\"temperature\":%.2f,\"humidity\":%.2f,\"pressure\":%.2f,\"rssi\":%d}",
           t, h, p, WiFi.RSSI());                 // ฝังค่าที่วัดได้และความแรงสัญญาณ Wi-Fi (RSSI)

  Serial.print(F("[PUB] "));                      // พิมพ์ prefix สำหรับดูใน Serial
  Serial.println(payload);                        // พิมพ์เนื้อหา JSON ที่จะส่ง

  mqtt.publish(TELE_TOPIC, payload, false);       // ส่งขึ้น MQTT ที่หัวข้อ telemetry (retain=false สำหรับข้อมูลไหล)

  digitalWrite(LED_PIN, HIGH); delay(20);         // กะพริบ LED สั้น ๆ เพื่อยืนยันการส่ง
  digitalWrite(LED_PIN, LOW);                     // ปิด LED
}

// ---------------------- setup(): รันครั้งเดียวตอนบูต ----------------------
void setup() {
  pinMode(LED_PIN, OUTPUT);                       // ตั้งโหมดขา LED เป็นเอาต์พุต
  digitalWrite(LED_PIN, LOW);                     // เริ่มต้นให้ LED ปิดไว้ก่อน

  Serial.begin(115200);                           // เปิดพอร์ต Serial ที่ความเร็ว 115200 bps
  delay(300);                                     // หน่วงสั้น ๆ เพื่อให้ Serial พร้อม
  Serial.println();                               // ขึ้นบรรทัดใหม่
  Serial.println(F("=== ESP32+BME280+MQTT (Robust Reconnect) ===")); // พิมพ์หัวเรื่อง

  WiFi.onEvent(onWiFiEvent);                      // ลงทะเบียน callback เพื่อรับเหตุการณ์ Wi-Fi

  if (bme.begin(BME280_I2C_ADDR)) {               // พยายามเริ่มต้น BME280 ที่ที่อยู่ 0x76
    bmeReady = true;                              // ถ้าพบและตั้งค่าผ่าน ให้ธงพร้อมใช้งาน = true
    Serial.println(F("[BME280] initialized (0x76)")); // แจ้งผลสำเร็จ
  } else {                                        // ถ้าไม่พบที่ 0x76
    Serial.println(F("[BME280] NOT found at 0x76, try 0x77...")); // แจ้งว่าจะลอง 0x77
    if (bme.begin(0x77)) {                        // ลองเริ่มต้นที่ 0x77
      bmeReady = true;                            // ถ้าสำเร็จ ให้ธงพร้อมใช้งาน = true
      Serial.println(F("[BME280] initialized (0x77)")); // แจ้งผลสำเร็จ
    } else {                                      // ถ้ายังไม่พบ
      Serial.println(F("[BME280] sensor not found, continue without sensor")); // แจ้งว่าไม่พบเซนเซอร์
      bmeReady = false;                           // ตั้งธงว่าไม่พร้อม (ลูปยังคงทำงานส่วนอื่น ๆ ต่อได้)
    }
  }

  ensureWiFi();                                   // เริ่มขั้นตอนการเชื่อม Wi-Fi รอบแรก (ไม่บล็อก)
}

// ---------------------- loop(): วนซ้ำตลอดเวลาที่อุปกรณ์ทำงาน ----------------------
void loop() {
  ensureWiFi();                                   // 1) ดูแลการเชื่อมต่อ Wi-Fi (ถ้าหลุดจะพยายามต่อใหม่เป็นช่วง ๆ)
  ensureMQTT();                                   // 2) ดูแลการเชื่อมต่อ MQTT (ทำหลัง Wi-Fi พร้อม)

  if (mqtt.connected()) mqtt.loop();              // 3) ถ้า MQTT เชื่อมแล้ว ให้หมุน loop ภายในเพื่อจัดการ keep-alive/callback

  unsigned long now = millis();                   // อ่านเวลาปัจจุบัน
  if (now - lastPublish >= PUBLISH_EVERY) {       // 4) ถึงรอบเวลาที่ต้องส่งเทเลเมทรีหรือยัง?
    lastPublish = now;                            // บันทึกเวลาไว้
    publishTelemetry();                           // เรียกฟังก์ชันส่งข้อมูล
  }

  delay(5);                                       // หน่วงสั้น ๆ เพื่อลดภาระ CPU และคงความ responsive ของลูป
}
</code></pre>
<p>ตัวอย่างที่ 4 เพิ่มการต่อ LED ที่ขา 23 เพื่อทดสอบการใช้ MQTT Subscribe</p>
<pre><code>// ====== ESP32 + BME280 + MQTT ======
//  - Publish ข้อมูลเซนเซอร์ → กะพริบ LED บนบอร์ดสั้น ๆ
//  - Subscribe หัวข้อ led_status → ควบคุม LED ภายนอก (ขาแยกต่างหาก)
#define MQTT_VERSION MQTT_VERSION_3_1_1           // โปรโตคอล MQTT 3.1.1

// ---------------------- รวมไลบรารีที่ใช้ ----------------------
#include &lt;WiFi.h&gt;                                 // Wi-Fi ของ ESP32
#include &lt;PubSubClient.h&gt;                         // MQTT client
#include &lt;Wire.h&gt;                                 // I2C
#include &lt;Adafruit_Sensor.h&gt;                      // Unified sensor
#include &lt;Adafruit_BME280.h&gt;                      // ไดรเวอร์ BME280

// ---------------------- การตั้งค่า Wi-Fi / MQTT ----------------------
const char* WIFI_SSID     = "YOUR_WIFI_SSID";     // ชื่อเครือข่าย Wi-Fi ที่จะเชื่อมต่อ
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD"; // รหัสผ่านของ Wi-Fi

const char* MQTT_HOST     = "192.168.1.10";       // IP/โฮสต์ของ MQTT broker (เช่น เครื่อง Node-RED หรือ Mosquitto)
const uint16_t MQTT_PORT  = 1883;                 // พอร์ต MQTT (ปกติ 1883 สำหรับแบบไม่เข้ารหัส)

// หัวข้อที่ใช้สื่อสาร
const char* TELE_TOPIC   = "vecskill/telemetry";  // ส่งค่าจากเซนเซอร์
const char* STATUS_TOPIC = "vecskill/status";     // สถานะ online/offline
const char* LED_TOPIC    = "led_status";          // หัวข้อที่ใช้สั่ง LED ภายนอก

// กำหนดช่วงเวลา (มิลลิวินาที)
const uint32_t WIFI_RETRY_MS = 10'000;            // พยายามต่อ Wi-Fi ใหม่ทุก 10 วิ
const uint32_t MQTT_RETRY_MS = 5'000;             // พยายามต่อ MQTT ใหม่ทุก 5 วิ
const uint32_t PUBLISH_EVERY = 2'000;             // ส่ง telemetry ทุก 2 วิ

// ที่อยู่ I2C ของ BME280
#define BME280_I2C_ADDR 0x76                      // ส่วนมากใช้ 0x76 (ถ้าไม่ใช่ลอง 0x77)

// ---------------------- สร้างออบเจ็กต์หลัก ----------------------
WiFiClient      espClient;                        // TCP client
PubSubClient    mqtt(espClient);                  // MQTT client
Adafruit_BME280 bme;                              // เซนเซอร์ BME280

// ประกาศ prototype callback MQTT
void mqttCallback(char* topic, byte* payload, unsigned int length);

// ---------------------- ตัวแปรสถานะเวลา ----------------------
unsigned long lastWifiAttempt = 0;
unsigned long lastMqttAttempt = 0;
unsigned long lastPublish     = 0;
bool          bmeReady        = false;

// ---------------------- ขา LED สำหรับงานต่าง ๆ ----------------------
// 1) LED บนบอร์ด: ใช้ยืนยันตอน publish
#if defined(LED_BUILTIN)
const int LED_PUB_PIN = LED_BUILTIN;              // ถ้าบอร์ดประกาศ LED_BUILTIN ไว้
#else
const int LED_PUB_PIN = 2;                        // ถ้าไม่มีก็ใช้ GPIO2 ซึ่งมักมี LED ออนบอร์ด
#endif

// 2) LED ภายนอก: ใช้รับคำสั่งจาก MQTT (led_status)
const int LED_SUB_PIN = 23;                       // ต่อ LED + R จากขานี้ → GND

// ---------------------- ตัวจัดการเหตุการณ์ Wi-Fi ----------------------
void onWiFiEvent(WiFiEvent_t event) {
  switch (event) {
    case ARDUINO_EVENT_WIFI_STA_CONNECTED:
      Serial.println(F("[WiFi] Connected to AP"));
      break;

    case ARDUINO_EVENT_WIFI_STA_GOT_IP:
      Serial.print(F("[WiFi] Got IP: "));
      Serial.println(WiFi.localIP());
      break;

    case ARDUINO_EVENT_WIFI_STA_DISCONNECTED:
      Serial.print(F("[WiFi] Disconnected, reason="));
      //Serial.println(WiFi.reasonCode());
      if (mqtt.connected()) mqtt.disconnect();    // หลุด Wi-Fi ให้ตัด MQTT ด้วย
      break;

    default:
      break;
  }
}

// ---------------------- ดูแลการเชื่อม Wi-Fi (ไม่บล็อก loop) ----------------------
bool ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) return true; // ถ้าเชื่อมอยู่แล้วก็จบ

  unsigned long now = millis();
  if (now - lastWifiAttempt < WIFI_RETRY_MS)
    return false;                                 // ยังไม่ถึงเวลาลองใหม่

  lastWifiAttempt = now;
  Serial.print(F("[WiFi] Connecting to "));
  Serial.print(WIFI_SSID);
  Serial.println(F(" ..."));

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(false);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  return false;
}

// ---------------------- ดูแลการเชื่อม MQTT (ไม่บล็อก loop) ----------------------
bool ensureMQTT() {
  if (WiFi.status() != WL_CONNECTED) return false;  // ยังไม่มี Wi-Fi
  if (mqtt.connected()) return true;                // ต่อ MQTT อยู่แล้ว

  unsigned long now = millis();
  if (now - lastMqttAttempt < MQTT_RETRY_MS)
    return false;                                   // ยังไม่ถึงรอบลองใหม่

  lastMqttAttempt = now;

  mqtt.setServer(MQTT_HOST, MQTT_PORT);
  mqtt.setKeepAlive(30);
  mqtt.setSocketTimeout(30);

  char     clientId[40];
  uint64_t mac = ESP.getEfuseMac();                 // ใช้ MAC ให้ clientId ไม่ซ้ำ
  snprintf(clientId, sizeof(clientId), "ESP32_%llX", mac);

  Serial.print(F("[MQTT] Connecting as "));
  Serial.print(clientId);
  Serial.print(F(" to "));
  Serial.print(MQTT_HOST);
  Serial.print(F(":"));
  Serial.println(MQTT_PORT);

  bool ok = mqtt.connect(
    clientId,
    STATUS_TOPIC,                                   // willTopic
    1,                                              // willQoS
    true,                                           // willRetain
    "offline"                                       // willMessage
  );

  if (ok) {
    Serial.println(F("[MQTT] Connected"));
    mqtt.publish(STATUS_TOPIC, "online", true);     // แจ้งว่า online

    // สมัครรับ topic สำหรับควบคุม LED ภายนอก
    if (mqtt.subscribe(LED_TOPIC, 1)) {
      Serial.println(F("[MQTT] Subscribed to 'led_status'"));
    } else {
      Serial.println(F("[MQTT] Failed to subscribe 'led_status'"));
    }
  } else {
    Serial.print(F("[MQTT] Failed, rc="));
    Serial.println(mqtt.state());
  }
  return ok;
}

// ---------------------- callback เมื่อมีข้อความ MQTT เข้ามา ----------------------
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  Serial.print(F("[MQTT] Message arrived ["));
  Serial.print(topic);
  Serial.print(F("] "));

  for (unsigned int i = 0; i < length; i++) {
    Serial.print((char)payload[i]);
  }
  Serial.println();

  // ถ้าเป็นหัวข้อ led_status → ควบคุม LED ภายนอก
  if (strcmp(topic, LED_TOPIC) == 0) {
    char msg[10];
    if (length >= sizeof(msg)) length = sizeof(msg) - 1;  // กัน overflow
    memcpy(msg, payload, length);
    msg[length] = '\0';

    int value = atoi(msg);                                // "0"/"1" → int
    if (value == 0) {
      digitalWrite(LED_SUB_PIN, LOW);                     // ดับ LED ภายนอก
      Serial.println(F("[LED_SUB] OFF (from MQTT)"));
    } else if (value == 1) {
      digitalWrite(LED_SUB_PIN, HIGH);                    // ติด LED ภายนอก
      Serial.println(F("[LED_SUB] ON (from MQTT)"));
    } else {
      Serial.println(F("[LED_SUB] Unknown value (use 0 or 1)"));
    }
  }
}

// ---------------------- อ่าน BME280 แล้ว publish ขึ้น MQTT ----------------------
void publishTelemetry() {
  if (!bmeReady || !mqtt.connected()) return;     // ถ้าเซนเซอร์ไม่พร้อม หรือยังไม่มี MQTT

  float t = bme.readTemperature();                // °C
  float h = bme.readHumidity();                   // %RH
  float p = bme.readPressure() / 100.0F;          // hPa

  if (isnan(t) || isnan(h) || isnan(p)) {
    Serial.println(F("[BME280] NAN reading, skip"));
    return;
  }

  char payload[160];
  snprintf(payload, sizeof(payload),
           "{\"t\":%.2f,\"h\":%.2f,\"p\":%.2f,\"rssi\":%d}",
           t, h, p, WiFi.RSSI());

  Serial.print(F("[PUB] "));
  Serial.println(payload);

  mqtt.publish(TELE_TOPIC, payload, false);       // ส่ง telemetry

  // --- กะพริบ LED บนบอร์ดสั้น ๆ เพื่อยืนยันการ publish ---
  digitalWrite(LED_PUB_PIN, HIGH);
  delay(20);                                      // กระพริบ 20 ms
  digitalWrite(LED_PUB_PIN, LOW);
  // ----------------------------------------------------------
}

// ---------------------- setup() : รันครั้งเดียวตอนบูต ----------------------
void setup() {
  // ตั้งค่า LED ทั้งสองดวง
  pinMode(LED_PUB_PIN, OUTPUT);                   // LED บนบอร์ดสำหรับ publish
  digitalWrite(LED_PUB_PIN, LOW);

  pinMode(LED_SUB_PIN, OUTPUT);                   // LED ภายนอกสำหรับ subscribe
  digitalWrite(LED_SUB_PIN, LOW);

  Serial.begin(115200);
  delay(300);
  Serial.println();
  Serial.println(F("=== ESP32 + BME280 + MQTT + 2 LEDs ==="));

  WiFi.onEvent(onWiFiEvent);                      // จับเหตุการณ์ Wi-Fi
  mqtt.setCallback(mqttCallback);                 // ให้ MQTT เรียก mqttCallback เมื่อมีข้อความเข้า

  // เริ่มต้น BME280 (ลอง 0x76 ถ้าไม่เจอค่อยลอง 0x77)
  if (bme.begin(BME280_I2C_ADDR)) {
    bmeReady = true;
    Serial.println(F("[BME280] initialized (0x76)"));
  } else {
    Serial.println(F("[BME280] NOT found at 0x76, try 0x77..."));
    if (bme.begin(0x77)) {
      bmeReady = true;
      Serial.println(F("[BME280] initialized (0x77)"));
    } else {
      Serial.println(F("[BME280] sensor not found, continue without sensor"));
      bmeReady = false;
    }
  }

  ensureWiFi();                                   // เริ่มกระบวนการต่อ Wi-Fi
}

// ---------------------- loop() : รันวนตลอดเวลา ----------------------
void loop() {
  ensureWiFi();                                   // 1) ดูแล Wi-Fi
  ensureMQTT();                                   // 2) ดูแล MQTT

  if (mqtt.connected()) mqtt.loop();              // 3) ให้ MQTT จัดการ keep-alive / callback

  unsigned long now = millis();
  if (now - lastPublish >= PUBLISH_EVERY) {       // 4) ถึงเวลาส่ง telemetry หรือยัง
    lastPublish = now;
    publishTelemetry();
  }

  delay(5);                                       // พัก CPU นิดหน่อย
}</code></pre>

<p>หลังจากอัปโหลดโค้ดนี้ไปยัง ESP32 ของคุณแล้ว ให้เปิด Serial Monitor (ตั้งค่า Baud Rate เป็น 115200) เพื่อดู Log การเชื่อมต่อและการส่งข้อมูล</p>

<hr style="margin: 40px 0" />

<h2>3. 📊 การตั้งค่าฝั่ง Node-RED (เบื้องต้น)</h2>
<p>บน Server ของคุณที่รัน Node-RED อยู่ ให้สร้าง Flow ง่ายๆ เพื่อรับข้อมูลจาก MQTT ดังนี้:</p>
<ol>
  <li>
    <strong>ลาก Node `mqtt in` มาวาง:</strong>
    <ul>
      <li>
        <strong>Server:</strong> คลิกไอคอนดินสอเพื่อตั้งค่า MQTT Broker ของคุณ (ต้องเป็น Server และ Port เดียวกับที่ ESP32 ชี้ไป, เช่น `localhost:1883` หาก MQTT Broker รันบนเครื่องเดียวกับ Node-RED)
      </li>
      <li><strong>Topic:</strong> ตั้งค่าเป็น `vecskill` (หรือ Topic ที่คุณกำหนดในโค้ด ESP32)</li>
      <li><strong>Output:</strong> ตั้งค่าเป็น **a parsed JSON object** (สำคัญมาก! เพื่อให้ Node-RED แปลงข้อมูลที่ได้รับเป็น Object)</li>
      <li><strong>Name:</strong> ตั้งชื่อ Node (เช่น "ESP32 Sensor Data")</li>
    </ul>
    <div class="image-container">
      <p>การตั้งค่า Node `mqtt in` ใน Node-RED</p>
    </div>
  </li>
  <li>
    <strong>ลาก Node `debug` มาวาง:</strong>
    <ul>
      <li><strong>Output:</strong> ตั้งค่าเป็น `msg.payload` (ค่าเริ่มต้น)</li>
    </ul>
  </li>
  <li><strong>เชื่อมต่อ Node:</strong> ลากเส้นเชื่อมจาก Output ของ Node `mqtt in` ไปยัง Input ของ Node `debug`</li>
  <li><strong>Deploy Flow:</strong> คลิกปุ่ม "Deploy" สีแดงที่มุมบนขวา</li>
  <li>
    <strong>ตรวจสอบผลลัพธ์:</strong> เปิดหน้าต่าง Debug (คลิกไอคอนรูปแมลง <i class="fas fa-bug"></i> ที่แถบด้านขวา) คุณควรจะเห็นข้อมูล JSON ที่ ESP32 ส่งมา ปรากฏขึ้นทุกๆ 10 วินาที
    <pre><code class="language-json">{
  "temperature": 25.50,
  "humidity": 60.20,
  "pressure": 1012.34
}</code></pre>
    <div class="image-container">
      <p>ข้อมูล JSON จาก ESP32 แสดงในหน้าต่าง Debug ของ Node-RED</p>
    </div>
  </li>
</ol>

<h3>การนำไปใช้งานต่อ (เช่น บันทึกลงฐานข้อมูล)</h3>
<p>เมื่อคุณได้รับข้อมูลใน Node-RED เป็น `msg.payload` ที่เป็น JSON Object แล้ว คุณสามารถนำไปใช้งานต่อได้หลากหลาย เช่น:</p>
<ul>
  <li>เชื่อมต่อไปยัง Node ฐานข้อมูล (เช่น `mysql`, `influxdb out`, `postgres`) เพื่อบันทึกข้อมูล</li>
  <li>ใช้ Node `function` เพื่อเขียน Logic เพิ่มเติม (เช่น ตรวจสอบค่า, คำนวณค่าเฉลี่ย)</li>
  <li>เชื่อมต่อไปยัง Node `dashboard` (ถ้าติดตั้ง node-red-dashboard) เพื่อสร้างกราฟแสดงผลแบบ Real-time</li>
  <li>ส่งการแจ้งเตือนผ่าน Line, Email, หรือ Service อื่นๆ เมื่อค่าเกินเกณฑ์ที่กำหนด</li>
</ul>
<p>ตัวอย่างนี้เป็นเพียงจุดเริ่มต้นในการสร้างระบบ IoT คุณสามารถนำหลักการนี้ไปประยุกต์ใช้กับเซ็นเซอร์ประเภทอื่นๆ หรือสร้าง Flow การทำงานที่ซับซ้อนขึ้นได้ตามความต้องการ</p>
