<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Motor Status Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.0.0/dist/chartjs-plugin-zoom.min.js"></script> <!-- Zoom plugin -->
  <style>
    .status-box {
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      color: #fff;
    }
    .status-on {
      background-color: #4caf50;
    }
    .status-off {
      background-color: #f44336;
    }
  </style>
</head>
<body>
  <div class="container mt-4">
    <h1 class="text-center">Motor Status Tracker</h1>

    <div class="text-center my-3">
      <div id="status-box" class="status-box status-off">
        <h2 id="status-text">Motor is OFF</h2>
        <p id="last-update">Last updated: --</p>
      </div>
    </div>

    <div class="my-4">
      <h3>Motor Uptime History</h3>
      <canvas id="historicalChart" width="400" height="200"></canvas>
    </div>

    <div class="my-4">
      <label for="start-date">Start Date: </label>
      <input type="date" id="start-date">
      <label for="end-date">End Date: </label>
      <input type="date" id="end-date">
    </div>
  </div>

<script>
  let chart;

  const formatDateMinute = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
  };

  const setDefaultDates = () => {
    const currentDate = new Date();
    const currentDateFormatted = formatDateMinute(currentDate); 
    document.getElementById('start-date').value = currentDateFormatted.substring(0, 10);
    document.getElementById('end-date').value = currentDateFormatted.substring(0, 10);
  };

  const initializeChart = () => {
    const ctx = document.getElementById('historicalChart').getContext('2d');
    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: [], 
        datasets: [{
          label: 'Motor Uptime (ON=1, OFF=0)',
          data: [], 
          borderColor: 'rgba(75, 192, 192, 1)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        scales: {
          x: {
            title: { display: true, text: 'Timestamp (Minute Intervals)' },
            ticks: {
              autoSkip: true,
              maxTicksLimit: 10,
              callback: function(value) {
                return value;  
              }
            }
          },
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Status (On=1, Off=0)' }
          }
        },

        plugins: {
          zoom: {
            zoom: {
              wheel: {
                enabled: true, 
              },
              pinch: {
                enabled: true, 
              },
              mode: 'xy', 
            },
            pan: {
              enabled: true, 
              mode: 'xy', 
            }
          }
        }
      }
    });
  };

  const generateIntervals = (startDate, endDate) => {
    let intervals = [];
    let currentTime = new Date(startDate);
    while (currentTime <= endDate) {
      intervals.push(new Date(currentTime));
      currentTime.setMinutes(currentTime.getMinutes() + 1); 
    }
    return intervals;
  };

  const convertTimestamp = (timestamp) => {
    return timestamp.replace(' ', 'T');  
  };

  const loadStatusAndHistory = () => {
    let startDate = document.getElementById('start-date').value || '2024-12-25'; 
    let endDate = document.getElementById('end-date').value || '2024-12-25'; 

    const start = new Date(startDate + ' 00:00:00'); 
    const end = new Date(endDate + ' 23:59:59'); 

    const intervals = generateIntervals(start, end);

    fetch(`https://domain.com/initial_check.php?start_date=${startDate}&end_date=${endDate}`)
      .then(res => res.json())
      .then(data => {
        if (!data || !data.status || !data.history) {
          console.error("Data is missing required fields.");
          return;
        }

        const statusBox = document.getElementById('status-box');
        const statusText = document.getElementById('status-text');
        const lastUpdate = document.getElementById('last-update');

        const currentTime = new Date();
        let motorIsGreen = false;

        if (data.last_update && data.last_update !== 'No history available') {
          const lastUpdateTime = new Date(data.last_update);
          const timeDifferenceInMinutes = (currentTime - lastUpdateTime) / (1000 * 60); 

          if (timeDifferenceInMinutes <= 1) {
            motorIsGreen = true;
          }

          lastUpdate.textContent = `Last updated: ${data.last_update}`;
        } else {
          lastUpdate.textContent = 'Last updated: No logs found';
        }

        if (motorIsGreen) {
          statusBox.className = 'status-box status-on';  
          statusText.textContent = 'Motor is ON';
        } else {
          statusBox.className = 'status-box status-off';  
          statusText.textContent = 'Motor is OFF';
        }

        const labels = intervals.map(date => formatDateMinute(date)); 

        let values = new Array(labels.length).fill(0); 

        data.history.forEach(entry => {
          const entryTime = convertTimestamp(entry.timestamp);
          console.log(`Processing API Entry Time: ${entry.timestamp}, Converted: ${entryTime}`);

          const entryDate = new Date(entry.timestamp);
          const entryMinuteLabel = formatDateMinute(entryDate); 

          const entryIndex = labels.findIndex(label => label === entryMinuteLabel);

          if (entryIndex >= 0) {
            console.log(`Found Match! Setting values[${entryIndex}] to ${entry.status === 'ON' ? 1 : 0}`);
            values[entryIndex] = entry.status === 'ON' ? 1 : 0;
          }
        });

        updateChart(labels, values);
      })
      .catch(err => {
        console.error('Failed to fetch motor status:', err);
      });
  };

  const updateChart = (labels, data) => {
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
  };

  document.getElementById('start-date').addEventListener('change', loadStatusAndHistory);
  document.getElementById('end-date').addEventListener('change', loadStatusAndHistory);

  initializeChart();
  setDefaultDates();
  loadStatusAndHistory();
</script>
</body>
</html>