# Content Aggregator v2

## 📌 Proje Hakkında

Content Aggregator v2, farklı içerik sağlayıcılarından (provider) veri çeken, veritabanına kaydeden ve API üzerinden bu içerikleri sunan bir içerik yönetim sistemidir.

Proje, yüksek performanslı veri çekme ve filtreleme yetenekleri ile hem web uygulamaları hem de diğer sistemler için içerik sunmayı hedefler.

---

## 🛠️ Kullanılan Teknolojiler

- **PHP 8.2** – Modern PHP sürümü ile tip güvenliği ve performans.
- **Symfony 7.3** – Framework olarak, MVC ve servis tabanlı mimari sağlamak için.
- **Doctrine ORM** – Veritabanı yönetimi ve entity mapping.
- **MySQL / MariaDB** – Veritabanı.
- **PHPUnit** – Unit ve servis testleri için.
- **Symfony RateLimiter** – API rate limiting.
- **Cache (Symfony Cache)** – İçerik sorgularını hızlandırmak için.

---

## 💡 Mimari Kararlar

- **Servis Tabanlı Mimari**: `ProviderService` ile içerik çekme, kaydetme ve cache yönetimi tek bir servis altında toplanmıştır.
- **Cache Kullanımı**: İçerik sorgularında cache ile performans artırılmıştır. `searchContentsCached` ve `countContentsCached` metodlarıyla hızlı veri erişimi sağlanır.
- **Rate Limiter**: API endpoint’leri `RateLimiterFactory` ile korunur; böylece aşırı istekler kontrol altına alınır.
- **Repository & Entity Yapısı**: `Provider` ve `Content` entity’leri ile veri bütünlüğü ve esnek sorgular sağlanır.
- **Unit Test Odaklı Gelişim**: Servislerin doğru çalıştığını doğrulamak için PHPUnit kullanılmıştır. Testler, cache ve provider client gibi bağımlılıkları mock’layarak izole çalışır.

---

## ⚙️ Kurulum

1. **Proje Klonlama**
```bash
git clone <repo-url> content-aggregator-v2
cd content-aggregator-v2
```

2. **Bağımlılıkları Yükleme**
```bash
composer install
```

3. **.env Dosyası**
```bash
cp .env.example .env
# Gerekli veritabanı ve API ayarlarını yapın
```

4. **Veritabanı Kurulumu**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Cache Temizleme**
```bash
php bin/console cache:clear
```

6. **Uygulamayı Çalıştırma (Development)**
```bash
symfony server:start
# veya
php -S localhost:8000 -t public
```

---

## 📝 API Endpoint’leri

### GET /api/contents: 
Bu API, içerikleri (content) listelemek, filtrelemek, sıralamak ve sayfalama yapmak için kullanılır.  
Endpoint: `/api/contents`  
Desteklenen yöntemler: **POST** (tavsiye edilir) ve **GET**

## 🔹 Request Body (JSON)

Aşağıdaki alanlar istek gövdesinde gönderilebilir:

| Parametre | Tip | Varsayılan | Açıklama |
|------------|------|-------------|-----------|
| `draw` | int | 1 | DataTables entegrasyonu için kullanılır (isteğe bağlı). |
| `start` | int | 0 | Kaçıncı kayıttan başlanacağını belirtir (sayfalama başlangıcı). |
| `length` | int | 10 | Sayfa başına kaç kayıt döneceğini belirtir. |
| `type` | string | null | İçerik türü (örnek: `"video"`, `"article"`, `"podcast"`). |
| `keyword` | string | null | Başlık veya etiket içinde arama yapmak için kelime. |
| `order` | array | `[{ "column": 2, "dir": "DESC" }]` | DataTables uyumlu sıralama formatı. |
| `orderColumn` | string | `"score"` | Alternatif sıralama parametresi (örnek: `"views"`, `"likes"`, `"score"`, `"published_at"`). |
| `orderDir` | string | `"DESC"` | Sıralama yönü (`ASC` veya `DESC`). |

> ⚙️ `order` ve `orderColumn` birlikte kullanılabilir.  
> Eğer `orderColumn` verilirse, `order` parametresi yok sayılır.

---
## 🔹 Örnek İstekler

### Tüm içerikleri getir
```json
{
    "start": 0,
    "length": 10
}
```

### Belirli türe göre filtrele
```json
{
    "start": 0,
    "length": 5,
    "type": "video"
}
```

### Anahtar kelimeye göre arama
```json
{
    "keyword": "programming",
    "length": 5
}
```

### Sıralama örnekleri
a) Datatable uyumlu format
```json
{
    "order": [
        { "column": 2, "dir": "DESC" }
    ]
}
```
> Burada column index’i ["title", "type", "score", "views"] dizisine göre belirlenir.

b) Basit sıralama formatı
```json
{
    "orderColumn": "score",
    "orderDir": "DESC"
}
```
> Geçerli kolonlar: score, views, likes, published_at

### Sayfalama Örneği
```json
{
    "start": 10,
    "length": 10,
    "orderColumn": "score",
    "orderDir": "DESC"
}
```

### Response (JSON)
| Alan              | Tip   | Açıklama                                                  |
|-------------------|-------|-----------------------------------------------------------|
| `draw`            | int   | Gönderilen draw değeri geri döner (isteğe bağlı).         |
| `recordsTotal`    | int   | Veritabanındaki toplam içerik sayısı.                     |
| `recordsFiltered` | int   | Uygulanan filtreler sonrası bulunan toplam içerik sayısı. |
| `data`            | array | İçerik listesi.                                           |


### Örnek Yanıt
Response:
````json
{
    "draw": 1,
    "recordsTotal": 8,
    "recordsFiltered": 4,
    "data": [
        {
            "id": 68,
            "provider_id": 6,
            "provider_item_id": "v4",
            "title": "Go Testing Best Practices",
            "type": "video",
            "views": 12000,
            "likes": 950,
            "reactions": null,
            "comments": null,
            "reading_time": 17,
            "published_at": "2024-03-12 14:20:00",
            "tags": "[\"programming\",\"testing\",\"best-practices\"]",
            "score": 33.04,
            "created_at": "2025-10-05 22:09:22",
            "updated_at": "2025-10-05 22:09:22"
        }
    ]
}
`````

### Rate Limiting: Her IP adresi için 10 saniyede en fazla 5 istek.

- Her IP adresi için saniyede belirli sayıda istek izni vardır.
- Limit aşıldığında yanıt şu formatta döner:
```json
{
    "status": "error",
    "message": "Too many requests. Please wait.",
    "retry_after": 10
}
```
(`retry_after` saniye cinsindendir.)

---

### Hata Kodları

| HTTP Kodu                   | Durum | Açıklama                                                  |
| --------------------------- | ----- | --------------------------------------------------------- |
| `200 OK`                    | ✅     | Başarılı yanıt                                            |
| `400 Bad Request`           | ⚠️    | Eksik veya hatalı parametre                               |
| `429 Too Many Requests`     | 🚫    | Rate limiter tarafından engellendi (çok sık istek atıldı) |
| `500 Internal Server Error` | 💥    | Sunucu hatası                                             |

---

## Notlar

- GET metodu da desteklenir ama POST önerilir.
- keyword boş string ("") gönderilirse filtre uygulanmaz.
- orderColumn değeri izin verilen kolonlardan biri olmalıdır (score, views, likes, published_at).
- Veriler cache’den gelir, 300 saniye (5 dakika) boyunca saklanır.
- Yeni içerik eklendiğinde ilgili cache otomatik olarak temizlenir.

---

## ✅ Testler

Projede PHPUnit ile servis ve controller testleri bulunmaktadır. Testleri çalıştırmak için:
```bash
php bin/phpunit
```

Test kapsamı:

- `ProviderService` - İçerik çekme, kaydetme ve cache mekanizması.
- `ContentController` - API endpoint doğru veri döndürüyor mu, rate limit çalışıyor mu..

---

## 📚 Notlar
- `FetchContentsCommand` ve `ImportContentsCommand` gibi arka plan komutları artık kullanılmamaktadır.
- Proje tamamen servis tabanlı ve API odaklıdır.
- Cache ve rate limiter sayesinde performans ve API güvenliği sağlanmaktadır.
