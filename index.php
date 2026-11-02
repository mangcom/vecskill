<?php
// cache-busting อัตโนมัติ: อัปเดตไฟล์แล้ว browser จะโหลดของใหม่ทันทีโดยไม่ต้องพึ่ง Hard Refresh
$v = function (string $path): string {
    $full = __DIR__ . '/' . $path;
    return $path . (is_file($full) ? '?v=' . filemtime($full) : '');
};
?>
<!doctype html>
<html lang="th">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>คู่มือการติดตั้งและฝึกซ้อมการแข่งขันทักษะ อาชีพนักพัฒนาระบบ สาขาวิชาเทคโนโลยีสารสนเทศ</title>
    <!-- <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet" /> -->
    <link rel="stylesheet" href="<?= $v('style.css') ?>" />
    <link rel="stylesheet" href="assets/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" type="text/css" />
  </head>
  <body>
    <div id="container">
      <aside class="sidebar" id="sidebar">
        <header class="sidebar-header">
          <button class="collapse-btn" id="collapse-sidebar-btn" title="Toggle Sidebar">
            <i class="fas fa-angle-left"></i>
          </button>
          <h2>DevOps Guide</h2>
          <button class="close-btn" id="close-menu-btn">&times;</button>
        </header>
        <nav class="menu" id="main-menu">
          <ul>
            <li class="active">
              <a href="/" data-page="project-overview"><i class="fas fa-tachometer-alt fa-fw"></i><span> ภาพรวมการพัฒนา</span></a>
            </li>
            <li>
              <a href="/vmware-quickstart" data-page="vmware-quickstart"><i class="fas fa-compact-disc fa-fw"></i><span> Quick Start (VMWare)</span></a>
            </li>

            <li class="menu-section-label"><span>สำหรับผู้เข้าแข่งขัน (ทีม)</span></li>
            <li>
              <a href="/ubuntu-setup" data-page="ubuntu-setup"><i class="fab fa-ubuntu fa-fw"></i><span> การเตรียม Ubuntu Server</span></a>
            </li>
            <li>
              <a href="/docker" data-page="docker"><i class="fab fa-docker fa-fw"></i><span> การติดตั้ง Docker</span></a>
            </li>
            <li>
              <a href="/nodejs-install" data-page="nodejs-install"><i class="fab fa-node-js fa-fw"></i><span> Node.js (Online)</span></a>
            </li>
            <li>
              <a href="/portainer" data-page="portainer"><i class="fas fa-cubes fa-fw"></i><span> การติดตั้ง Portainer</span></a>
            </li>
            <li>
              <a href="/chartdb" data-page="chartdb"><i class="fas fa-database fa-fw"></i><span> การติดตั้ง ChartDB</span></a>
            </li>
            <li>
              <a href="/technitium-dns" data-page="technitium-dns"><i class="fas fa-network-wired fa-fw"></i><span> การติดตั้ง DNS Server</span></a>
            </li>
            <li>
              <a href="/caddy-proxy" data-page="caddy-proxy"><i class="fas fa-route fa-fw"></i><span> Caddy Reverse Proxy</span></a>
            </li>
            <li>
              <a href="/caddy-internal-ca" data-page="caddy-internal-ca"><i class="fas fa-lock fa-fw"></i><span> การทำ HTTPS ภายใน</span></a>
            </li>
            <li>
              <a href="/npm-proxy" data-page="npm-proxy"><i class="fas fa-random fa-fw"></i><span> Nginx Proxy Manager</span></a>
            </li>
            <li>
              <a href="/registry" data-page="registry"><i class="fas fa-database fa-fw"></i><span> การติดตั้ง Registry & UI</span></a>
            </li>
            <li>
              <a href="/gitlab" data-page="gitlab"><i class="fab fa-gitlab fa-fw"></i><span> การติดตั้ง GitLab</span></a>
            </li>
            <li>
              <a href="/basic-git-usage" data-page="basic-git-usage"><i class="fab fa-git-alt fa-fw"></i><span> การใช้งาน Git เบื้องต้น</span></a>
            </li>
            <li>
              <a href="/runner" data-page="runner"><i class="fas fa-running fa-fw"></i><span> การติดตั้ง Gitlab Runner</span></a>
            </li>
            <li>
              <a href="/cicd" data-page="cicd"><i class="fas fa-cogs fa-fw"></i><span> การใช้งาน Gitlab CI/CD</span></a>
            </li>
            <li>
              <a href="/cicd-example-1" data-page="cicd-example-1"><i class="fas fa-file-code fa-fw"></i><span> ตัวอย่าง Pipeline 1</span></a>
            </li>
            <li>
              <a href="/cicd-example-2" data-page="cicd-example-2"><i class="fas fa-layer-group fa-fw"></i><span> ตัวอย่าง Pipeline 2</span></a>
            </li>
            <li>
              <a href="/owasp-zap" data-page="owasp-zap"><i class="fas fa-shield-alt fa-fw"></i><span> OWASP ZAP (Security Scan)</span></a>
            </li>
            <li>
              <a href="/trivy" data-page="trivy"><i class="fas fa-bug fa-fw"></i><span> Trivy (Vulnerability Scan)</span></a>
            </li>
            <li>
              <a href="/sonarqube" data-page="sonarqube"><i class="fas fa-chart-line fa-fw"></i><span> SonarQube (Code Quality)</span></a>
            </li>
            <li>
              <a href="/swagger" data-page="swagger"><i class="fas fa-book fa-fw"></i><span> Swagger (API Docs)</span></a>
            </li>
            <li>
              <a href="/node-red" data-page="node-red"><i class="fa-brands fa-node-js fa-fw"></i><span> Node-Red</span></a>
            </li>
            <li>
              <a href="/iot-esp32" data-page="iot-esp32"><i class="fas fa-microchip fa-fw"></i><span> ตัวอย่าง IoT</span> </a>
            </li>

            <li class="menu-section-label"><span>สำหรับกรรมการ / เจ้าภาพ (เตรียมระบบกลาง)</span></li>
            <li>
              <a href="/apt-cacher-ng" data-page="apt-cacher-ng"><i class="fas fa-box-archive fa-fw"></i><span> Apt-Cacher-Ng</span></a>
            </li>
            <li>
              <a href="/local-mirror" data-page="local-mirror"><i class="fas fa-hdd fa-fw"></i><span> APT Mirror (Ubuntu)</span></a>
            </li>
            <li>
              <a href="/dnf-mirror" data-page="dnf-mirror"><i class="fab fa-redhat fa-fw"></i><span> DNF Mirror (CentOS/Rocky)</span></a>
            </li>
            <li>
              <a href="/nodejs-local-repo" data-page="nodejs-local-repo"><i class="fab fa-node-js fa-fw"></i><span> Node.js Local Repo</span></a>
            </li>
            <li>
              <a href="/devpi-pypi" data-page="devpi-pypi"><i class="fab fa-python fa-fw"></i><span> Devpi (Private PyPI)</span></a>
            </li>
            <li>
              <a href="/verdaccio" data-page="verdaccio"><i class="fab fa-npm fa-fw"></i><span> Verdaccio (NPM Registry)</span></a>
            </li>
            <li>
              <a href="/skill-eval-pwa" data-page="skill-eval-pwa"><i class="fas fa-clipboard-list fa-fw"></i><span> Hardware/Software ปวช.</span></a>
            </li>
            <li>
              <a href="/skill-eval-pws" data-page="skill-eval-pws"><i class="fas fa-clipboard-list fa-fw"></i><span> Hardware/Software ปวส.</span></a>
            </li>
            <li>
              <a href="/skill-pes" data-page="skill-pes"><i class="fas fa-file-alt fa-fw"></i><span> ตัวอย่าง ระบบประเมินครู</span></a>
            </li>
            <!-- <li><a href="/under-construction" data-page="under-construction"><i class="fas fa-cogs fa-fw"></i><span> Gitlab CI/CD</span></a></li>
                    <li><a href="/under-construction" data-page="under-construction"><i class="fas fa-running fa-fw"></i><span> Gitlab Runner</span></a></li>
                    <li><a href="/under-construction" data-page="under-construction"><i class="fas fa-hourglass-half fa-fw"></i><span> Nginx Proxy Manager</span></a></li>
                    <li><a href="/under-construction" data-page="under-construction"><i class="fas fa-hourglass-half fa-fw"></i><span> Cloudflare</span></a></li> -->
            <li>
              <a href="/statistics" data-page="statistics"><i class="fas fa-chart-bar fa-fw"></i><span> สถิติการเข้าชม</span></a>
            </li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <button class="menu-toggle" id="menu-toggle">&#9776;</button>

        <div id="content-display"></div>
      </main>
    </div>
    <footer class="main-footer">
      <p>จัดรวบรวมโดย นายพรชัย ตุ่นแก้ว | แผนกวิชาเทคโนโลยีสารสนเทศ | วิทยาลัยพณิชยการบางนา</p>
    </footer>
    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="<?= $v('script.js') ?>"></script>
  </body>
</html>
