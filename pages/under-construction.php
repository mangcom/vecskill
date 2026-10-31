<style>
  /* เพิ่ม style เฉพาะหน้านี้ */
  body {
    overflow: hidden; /* ซ่อน scrollbar เพื่อให้ animation ดูลื่นไหล */
  }

  .construction-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 60px); /* หักส่วน header/padding ของ content ออก */
    text-align: center;
    background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
    color: #333;
    padding: 40px 20px;
    box-sizing: border-box;
  }

  .construction-icon {
    font-size: 8em; /* ขนาดไอคอน */
    color: #ff9800; /* สีส้มสดใส */
    margin-bottom: 30px;
    animation: bounce-scale 2s infinite ease-in-out; /* Animation */
  }

  .construction-title {
    font-size: 3.5em;
    color: #2c3e50;
    margin-bottom: 20px;
    animation: fadeIn 1.5s ease-out;
  }

  .construction-message {
    font-size: 1.5em;
    color: #555;
    max-width: 800px;
    margin-bottom: 40px;
    animation: fadeIn 2s ease-out;
  }

  .construction-progress {
    width: 80%;
    max-width: 600px;
    background-color: #e0e0e0;
    border-radius: 20px;
    height: 25px;
    overflow: hidden;
    margin-bottom: 20px;
    animation: fadeIn 2.5s ease-out;
  }

  .construction-progress-bar {
    height: 100%;
    width: 0; /* เริ่มต้นที่ 0 */
    background-color: #4caf50; /* สีเขียว */
    border-radius: 20px;
    animation: fillProgress 4s ease-out forwards; /* Animation เติมเต็ม */
  }

  .construction-countdown {
    font-size: 2em;
    color: #34495e;
    margin-bottom: 20px;
    animation: fadeIn 3s ease-out;
  }

  /* Keyframe Animations */
  @keyframes bounce-scale {
    0%,
    100% {
      transform: translateY(0) scale(1);
    }
    50% {
      transform: translateY(-20px) scale(1.1);
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fillProgress {
    from {
      width: 0%;
    }
    to {
      width: 75%;
    } /* ตั้งค่าเปอร์เซ็นต์ที่ต้องการ */
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .construction-icon {
      font-size: 6em;
    }
    .construction-title {
      font-size: 2.5em;
    }
    .construction-message {
      font-size: 1.2em;
    }
    .construction-countdown {
      font-size: 1.5em;
    }
  }

  @media (max-width: 480px) {
    .construction-icon {
      font-size: 4em;
    }
    .construction-title {
      font-size: 1.8em;
    }
    .construction-message {
      font-size: 1em;
    }
    .construction-countdown {
      font-size: 1.2em;
    }
  }
</style>

<div class="construction-container">
  <i class="fas fa-tools construction-icon"></i>
  <h1 class="construction-title">หน้ากำลังอยู่ระหว่างการพัฒนา</h1>
  <p class="construction-message">เรากำลังทำงานอย่างหนักเพื่อนำเสนอเนื้อหาที่ยอดเยี่ยมให้กับคุณ หน้านี้จะพร้อมใช้งานในไม่ช้า กรุณาเข้าชมอีกครั้งภายหลัง</p>

  <div class="construction-progress">
    <div class="construction-progress-bar" style="width: 0" id="progress-bar"></div>
  </div>
  <div class="construction-countdown" id="countdown"></div>

  <p style="font-size: 0.9em; color: #777; animation: fadeIn 3.5s ease-out">ขออภัยในความไม่สะดวก</p>
</div>

<script>
  // JavaScript สำหรับจัดการ ProgressBar และ Countdown
  document.addEventListener("DOMContentLoaded", function () {
    const progressBar = document.getElementById("progress-bar");
    const countdownElement = document.getElementById("countdown");

    // ตั้งค่าเริ่มต้นของ Progress Bar
    const targetProgress = 75; // เปอร์เซ็นต์ความคืบหน้าที่ต้องการ
    progressBar.style.width = targetProgress + "%";

    // ตั้งค่า Countdown
    const targetDate = new Date();
    targetDate.setDate(targetDate.getDate() + 7); // กำหนดให้เป็น 7 วันข้างหน้า

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = targetDate.getTime() - now;

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      if (distance < 0) {
        clearInterval(countdownInterval);
        countdownElement.innerHTML = "หน้านี้พร้อมแล้ว!";
      } else {
        countdownElement.innerHTML = `เหลืออีก: ${days} วัน ${hours} ชั่วโมง ${minutes} นาที ${seconds} วินาที`;
      }
    }

    const countdownInterval = setInterval(updateCountdown, 1000);
    updateCountdown(); // เรียกครั้งแรกเพื่อให้แสดงผลทันที
  });
</script>
