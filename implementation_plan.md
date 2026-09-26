# Akıllı Spor & Dinamik Hipertrofi Platformu (v2.1)
## Proje Uygulama Planı (Implementation Plan)

Bu doküman, cPanel paylaşımlı/VPS hosting üzerinde koşacak olan akıllı spor platformunun sıfırdan canlıya alınma sürecini, mimari fazlarını, dosya dökümlerini ve doğrulama testlerini içerir.

---

### Faz 1: Altyapı, Veritabanı ve Çekirdek Güvenlik Mimarisi
**Amaç:** Veritabanı tablolarının kurulması, PDO veritabanı sürücüsü, oturum güvenliği ve cPanel sunucu sertleştirmesinin tamamlanması.

- [x] **Görev 1.1: Web Sunucu Sertleştirmesi (`.htaccess`)**
  - Tüm HTTP trafiğini HTTPS'e 301 yönlendirmesi.
  - HSTS (`Strict-Transport-Security: max-age=31536000; includeSubDomains`).
  - `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`.
  - Hassas dosyalara (`config/`, `.sql`, `.env`) doğrudan erişim yasağı.
- [x] **Görev 1.2: Veritabanı Şemasının Dağıtımı (`database/schema.sql`)**
  - 14 temel tablonun InnoDB motoru ve UTF8MB4 karakter setiyle oluşturulması.
  - Foreign key bütünlük kısıtları (`ON DELETE CASCADE` / `ON DELETE RESTRICT`).
  - Standart 50 egzersizin tohumlama verisi (tohumlama scriptinde `movement_pattern` ve `exercise_joint_stress` ilişkileriyle birlikte).
- [x] **Görev 1.3: Çekirdek Sınıfların Kodlanması**
  - `config/database.php`: Singleton pattern ile PDO bağlantısı (`ERRMODE_EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE`).
  - `config/constants.php`: Brzycki/NSCA %1RM referans dizisi, RFI katsayıları, deload parametreleri.
  - `config/gemini.php`: API endpoint, zaman aşımı ve konfigürasyon tanımları.
  - `core/Security.php`:
    * `generateCsrfToken()`, `validateCsrfToken()`
    * `checkRateLimit($ip, $type, $limit, $windowSeconds)` (`login_attempts` tablosu üzerinden)
    * `sanitizeOutput($str)`
    * `sessionGuard()` (`session_regenerate_id`, `HttpOnly`, `Secure`, `SameSite=Strict`)
  - `core/Router.php`, `core/Controller.php`, `core/Model.php`

---

### Faz 2: Kullanıcı Kimlik Doğrulama (Auth) & Onboarding Modülü
**Amaç:** Kullanıcı üyelik akışları, güvenli şifre sıfırlama, kalistenik kuvvet değerlendirmesi ve ekipman envanterinin toplanması.

- [x] **Görev 2.1: Auth Controller & Görünümler**
  - `controllers/AuthController.php` mantığı:
    * `register()`: Argon2id şifreleme, kullanıcı oluşturma, oturum başlatma ve `/onboarding` yönlendirmesi.
    * `login()`: IP bazlı brute-force kontrolü (5 hatalı denemede 15 dk blokaj), parola doğrulaması.
    * `logout()`: Oturum değişkenlerinin ve çerezlerin güvenli imhası.
    * `forgotPassword()` / `resetPassword()`: 32-byte kriptografik token, SHA-256 hashleme, 1 saatlik token süresi.
  - `views/auth/`: Tailwind CSS tabanlı mobil uyumlu login, register, forgot-password ve reset-password arayüzleri.
- [x] **Görev 2.2: Onboarding & RFI Motoru Entegrasyonu**
  - `services/RfiService.php`:
    * Şınav, chin-up, squat tekrarlarından 6 hareket paterni için tahmini başlangıç ağırlıklarının hesaplanması.
    * 0 çeken kullanıcılar için `absolute_beginner` regresyon basamaklarının tanımlanması.
  - `views/onboarding/assessment.php`: Kalistenik test giriş ekranı.
  - `views/onboarding/equipment.php`: Halter/dambıl bar ağırlığı ve plaka envanteri (`available_plates` JSON) seçim ekranı.

---

### Faz 3: Antrenman Programı Üretimi ve Gemini Fallback Mekanizması
**Amaç:** Kullanıcı seviyesine ve ekipmanına uygun haftalık iskelet programın üretilmesi ve API kesintisi durumunda statik şablonların devreye alınması.

- [x] **Görev 3.1: Gemini Entegrasyon Servisi (`services/GeminiWorkoutService.php`)**
  - Gemini API REST çağrısı (cURL, 5.0 saniye timeout).
  - Prompt optimizasyonu: Serbest hareket ismi yerine yalnızca veritabanındaki geçerli `id` ve `name` listesinin modele iletilmesi.
  - Gelen JSON cevabının validasyonu (Dönen tüm `exercise_id`lerin `exercises` tablosunda var olduğunun denetimi).
- [x] **Görev 3.2: Fallback ve Yeniden Deneme (Retry) Sistemi**
  - API zaman aşımına uğrarsa veya geçersiz JSON dönerse `user_programs.generation_source = 'fallback_template'` olarak işaretlenmesi.
  - Veritabanında kayıtlı Split/Full-Body statik şablon programının kullanıcıya atanması.
  - Yeniden deneme sayacının (`generation_retry_count`) yönetilmesi; 6 deneme / 6 saat sonrasında otomatik retry'ın sonlandırılması.
- [x] **Görev 3.3: Sakatlık & Ağrı Filtreleme Servisi (`services/InjuryFilterService.php`)**
  - Kullanıcı antrenman öncesi eklem ağrısı bildirdiğinde `exercise_joint_stress` tablosunu tarayarak riskli hareketleri programdan çıkaran ve güvenli alternatifleri atayan filtre.

---

### Faz 4: Canlı Antrenman Takip Ekranı & Dinamik Hipertrofi Motoru
**Amaç:** Set aralarında kullanıcının eforunu kaydeden, anlık ağırlık güncelleyen ve plaka dizilimini hesaplayan çekirdek motor.

- [x] **Görev 4.1: Dinamik Hipertrofi Motoru (`services/HypertrophyEngine.php`)**
  - Yön Kararı: Tek otorite `rir_bucket` ('0', '1-2', '3+') ve `form_breakdown` bayrağı.
  - Miktar Kararı: Epley formülü ile 1RM tahmini (`Weight * (1 + Reps / 30)`) ve NSCA tablosu.
  - **Birleşik Tavan Kuralı:** Tek setlik artışın `min(actual_weight * 0.10, 2.5)` formülüyle sınırlandırılması.
  - **Oturum Güvenlik Tavanı:** Aynı `session_id` içinde maksimum 3 artış ve oturumun ilk set ağırlığına göre en fazla +%20 artış sınırı.
- [x] **Görev 4.2: Plaka Çözücü Servisi (`services/PlateSolverService.php`)**
  - Bar demiri ağırlığını (`barbell_weight_kg` / `dumbbell_handle_weight_kg`) düştükten sonra kalan yükü mevcut döküm plakalara göre iki yana eşit paylaştıran algoritma.
- [x] **Görev 4.3: Canlı Antrenman Arayüzü (`views/workout/live_session.php`)**
  - Alpine.js ile reaktif form yönetimi.
  - Set girişi, RIR butonları, form bozulması anahtarı ve süre bazlı egzersizler için `duration_seconds` alanı.
  - AJAX ile `/api/log_set.php` post işlemi; sayfa yenilenmeden bir sonraki setin ağırlığının, plaka diziliminin ve dinlenme süresinin (gerekirse +30 sn uyarısıyla) ekranda güncellenmesi.

---

### Faz 5: Günlük Tartı, Trend Analizi ve Planlı Deload Otomasyonu
**Amaç:** Günlük vücut ağırlığı değişimlerini izleyerek program hacmini dengeleyen ve aşırı antrenmanı önleyen cron mekanizması.

- [x] **Görev 5.1: Tartı Kaydı ve Dashboard (`controllers/WeighInController.php`)**
  - Günlük kilo girişi, mükerrer kayıt engeli (`UNIQUE(user_id, logged_date)`).
  - Chart.js ile 7 günlük hareketli ortalama grafiği.
- [x] **Görev 5.2: Gece Senkronizasyon Betiği (`cron/nightly_sync.php`)**
  - cPanel Cron Job entegrasyonu (Her gece 00:00).
  - Haftalık kilo kaybı %1'in üzerindeyse set sayılarının %25 oranında orantılı azaltılması (minimum 2 set).
  - Kilo artışı/stabilitesinde haftalık çalışma hacminin %5 artırılması.
  - Deload Planlayıcı: 5 veya 6. haftada (40 yaş üstü kullanıcılarda 4. haftada) `is_deload_week = true` tanımlanması, ağırlıkların %30-40 düşürülmesi, setlerin yarıya indirilmesi ve tükenişin yasaklanması.
  - Başarısız AI programlarının arka planda yeniden denenmesi (`generation_retry_count < 6`).

---

### Faz 6: Test, Kalibrasyon ve Canlıya Geçiş (Production)
**Amaç:** Sistem fonksiyonlarının uçtan uca doğrulanması, güvenlik denetimi ve canlı sunucu yapılandırması.
