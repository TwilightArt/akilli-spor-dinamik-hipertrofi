# Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1)

> **cPanel Uyumlu, Kalistenik Tabanlı Kuvvet Analizi ve Canlı Set İçi Ağırlık Adaptasyon Motoru**

---

## ⚡ Genel Bakış
**Hipertrofi.ai**, sporcuların biyometrik verilerini, 4 temel kalistenik kuvvet testini ve mevcut ekipman envanterini baz alarak hipertrofi odaklı kişiselleştirilmiş haftalık antrenman programları oluşturan, antrenman esnasında ise tek dokunuşla **RIR (Reps in Reserve)** ve **Epley Formülü** ile sonraki setin ağırlığını canlı olarak adapte eden akıllı bir web platformudur.

---

## 🚀 Temel Özellikler

### 1. 4 Parametreli Kalistenik Kondisyon & Kuvvet Testi
* **İtiş Gücü (%25):** Nizami şınav sayısı (Yatay & dikey itiş katsayısı).
* **Çekiş Gücü (%25):** Barfiks / Chin-Up sayısı (Sırt ve biceps katsayısı).
* **Bacak Kapasitesi (%25):** Vücut ağırlığı squat sayısı.
* **Kor Stabilizasyonu (%25):** Ön plank tutuş süresi (sn).
* **Otomatik Regresyon Koruması:** 0 şınav veya 0 barfiks çeken sporcular için duvara şınav (*Wall Push-Up*), negatif barfiks (*Negative Chin-Up*) ve kapı havlu çekişi (*Door Towel Row*) otomatik atanır.

### 2. Canlı Set İçi Tek Otorite RIR Ağırlık Adaptasyonu
* **RIR 3+ (Hafif Geldi):** Epley formülü ile gerçek $1\text{RM} = W \times (1 + R_{\text{fail}} / 30)$ hesaplanır; anında sonraki set için **$+2.5\text{ kg}$ artış önerisi** sunulur.
* **RIR 1-2 (İdeal Hipertrofi):** Mekanik gerilimin altın standardı; ağırlık korunur, 90s dinlenme sayacı başlar.
* **RIR 0 (Tükeniş / Form Bozulması):** MSS yorgunluğu ve sakatlığı önlemek için ağırlık %10 düşürülür, dinlenmeye $+30\text{ sn}$ eklenir.

### 3. Akıllı Plaka ve Donanım Çözücü
* Kullanıcının sahip olduğu halter, dambıl ve 20, 15, 10, 5, 2.5, 1.25, 0.5 kg plakalarına göre takılabilir en optimize çalışma ağırlığını ve plaka dizilimini canlı gösterir.

### 4. Eklem Koruma Kalkanı (Joint Stress Matrix)
* Omuz, bel, diz, dirsek ve el bileği hassasiyeti bildiren sporcular için yüksek riskli hareketler elenerek eklem dostu varyasyonları devreye alınır.

### 5. Ağırlık & Güç Analitiği (Progressive Overload Grafikleri)
* **Zaman İçinde Gelişim Grafiği:** Çift eğrili Chart.js çizelgesi (Maksimum Ağırlık + Tahmini 1RM).
* **Hareket Hareket Zirve Kıyaslama:** Tüm egzersizlerde bugüne kadar kaç kiloya çıkıldığını gösteren yatay bar grafiği.
* **Detaylı Set Geçmişi & PR:** Kişisel rekor kırılan setler için `🏅 ZİRVE PR` rozetleri.

### 6. Biyomekanik Egzersiz Kütüphanesi
* **Genişletilmiş Veritabanı:** Kas grubu (Göğüs, Sırt, Omuz, Bacak, Kol, Kor), ekipman gereksinimi ve zorluk seviyesine göre gerçek zamanlı arama ve filtreleme.
* **Biyomekanik Parametreler:** Birincil/ikincil hedef kas grupları, eklem stres indeksleri ve seviyeye uygun regresyon/progresyon alternatifleri.

### 7. Hesaplayıcılar & İnteraktif Simülatörler
* **Günlük Su İhtiyacı:** Vücut ağırlığı, idman süresi, iklim şartları ve kreatin kullanımına göre Litre, ml ve bardak cinsinden anlık hidrasyon hedefi.
* **İnteraktif RPE & RIR Simülatörü:** NSCA yük yüzdesi tablosu baz alınarak 1RM, hedef tekrar ve RPE/RIR değerlerine göre ideal çalışma ağırlığı hesaplama ve renk kodlu referans matrisi.
* **1RM & TDEE:** Epley formülü ile tek tekrar maksimum tahmini ve Mifflin-St Jeor denklemiyle günlük kalori/makro ihtiyacı.

### 8. HIIT & Tabata Kronometresi (Web Audio Sesli Sayaç)
* **Zamanlama Şablonları:** Tabata (20/10 x 8), Klasik HIIT (40/20 x 10), EMOM ve serbest çalışma/dinlenme yapılandırması.
* **Web Audio API Entegrasyonu:** Sıfır harici ses dosyası bağımlılığıyla dinamik sentezlenen sesli uyarılar (Son 3 saniye geri sayım bip uyarısı, çalışma başlama tonu, dinlenme tonu, seans bitiş fanfarı).
* **Canlı İdman Senkronizasyonu:** Canlı antrenmandaki set arası dinlenme sayacına tam entegre son 3 saniye sesli uyarısı.

### 9. Antrenman Streak Sayacı & Offline-First Antrenman Defteri
* **Streak (Seri) Sayacı:** Bu haftaki idman adedi, kesintisiz hafta serisi ve toplam tamamlanan seans metriği.
* **Offline-First Defter:** Çevrimdışı öncelikli (`localStorage`), anlık enerji (1-5), uyku kalitesi, pump ve serbest antrenman notu tutma yeteneği.

### 10. Vücut Ölçüleri Takipçisi (Body Measurements)
* **Bölgesel Takip:** Boyun, omuz, göğüs, kol, bel, kalça, bacak, kalf ve tahmini yağ oranı (`body_fat_pct`) takibi.
* **Delta Analitiği:** Veritabanı (`body_measurements` tablosu) ve `localStorage` çift katmanlı kayıt mimarisi, ilk/son ölçüm değişim rozetleri.

### 11. JSON Veri İçe/Dışa Aktarma & Mobil PWA
* **Veri Taşınabilirliği:** Kullanıcının tüm antrenman geçmişini, ölçümlerini ve profil ayarlarını tek tıkla JSON olarak yedekleme ve doğrulayarak geri yükleme imkanı.
* **PWA Desteği:** `manifest.json`, Service Worker (v5 Önbellekleme) ve mobil ana ekrana eklenebilir tam ekran uygulama deneyimi.

---

## 🛠️ Teknoloji Yığını ve Mimari Kısıtlar
* **Backend:** PHP 8.2+ (Strict Types, OOP, MVC, PDO).
* **Veritabanı:** 15 İlişkisel Tablo (InnoDB, UTF8mb4 - `body_measurements` dahil) | MySQL 8.0+ / MariaDB 10.5+ & SQLite 3 (Yerel test için sıfır konfigürasyon otomatik bootstrap).
* **Frontend:** Vanilla JS, Alpine.js v3 (Reaktivite), Chart.js v4 (Grafikler), Tailwind CSS (CDN).
* **Ses & PWA:** Web Audio API (Sıfır harici ses dosyası bağımlılığı), Service Worker (v5 Önbellekleme), Web App Manifest.
* **Güvenlik:** Argon2id parola şifreleme, sıkı CSRF token doğrulaması, PDO Prepared Statements (SQLi Koruması), XSS kaçışları.
* **Hosting:** Paylaşımlı cPanel (LiteSpeed / Apache) veya VPS. **Node.js, Python veya arka plan daemon süreçleri gerektirmez.**

---

## 📦 Kurulum ve Çalıştırma

### Yerel Geliştirme (Local):
```bash
# 1. Depoyu klonlayın
git clone https://github.com/TwilightArt/akilli-spor-dinamik-hipertrofi.git
cd akilli-spor-dinamik-hipertrofi

# 2. PHP dahili sunucusunu başlatın (SQLite sıfır konfigürasyon ile otomatik başlar)
php -S 127.0.0.1:8000 index.php
```
Tarayıcınızdan `http://127.0.0.1:8000` adresine gidin.

### cPanel / VPS Canlı Kurulumu:
1. Depodaki tüm dosyaları `public_html` (veya ilgili alt klasöre) yükleyin.
2. cPanel MySQL Veritabanı Sihirbazı'ndan yeni bir veritabanı ve kullanıcı oluşturun.
3. `database/schema.sql` dosyasını phpMyAdmin üzerinden veritabanına içe aktarın.
4. `.env.example` dosyasını `.env` olarak kopyalayın ve veritabanı bilgilerinizi girin:
   ```env
   DB_DRIVER=mysql
   DB_HOST=localhost
   DB_NAME=veritabani_adiniz
   DB_USER=kullanici_adiniz
   DB_PASS=parolaniz
   ```
5. `.htaccess` dosyasının Apache/LiteSpeed tarafından okunduğundan emin olun (`mod_rewrite` aktif olmalıdır).

---

## 📄 Lisans
Bu proje özel ve tescilli mülkiyettir. Tüm hakları saklıdır.
