#include <ESP8266WiFi.h>
#include <WiFiClientSecure.h>

const char* ssid = "ssid_name";
const char* password = "wifi_password";
const char* host = "domain.com";
const char* endpoint = "/url.php";
const String authorizationKey = "auth_key";

WiFiClientSecure client;
const unsigned long WiFiTimeout = 10000;

void ensureWiFiConnected() {
  unsigned long startAttemptTime = millis();

  while (WiFi.status() != WL_CONNECTED) {
    if (millis() - startAttemptTime >= WiFiTimeout) {
      Serial.println("Wi-Fi connection timed out. Reconnecting...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);
      startAttemptTime = millis();
    }
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWi-Fi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void setup() {
  Serial.begin(115200);
  Serial.println("Connecting to Wi-Fi...");
  WiFi.begin(ssid, password);
  ensureWiFiConnected();
  client.setInsecure();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Wi-Fi is lost, reconnecting...");
    WiFi.reconnect();
    ensureWiFiConnected();
  }

  if (client.connect(host, 443)) {
    Serial.println("Connected to server - Sending GET request");

    String url = String(endpoint) + "?type=api_type";
    client.println("GET " + url + " HTTP/1.1");
    client.println("Host: " + String(host));
    client.println("Authorization: Bearer " + authorizationKey);
    client.println("Connection: close");
    client.println();
  } else {
    Serial.println("Failed to connect to the server");
  }

  client.stop();
  delay(1000);
}
