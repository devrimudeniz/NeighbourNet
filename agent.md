# Kalkan Social – Agent Hafıza Dosyası

Bu dosya, proje üzerinde yapılan son işleri ve bağlamı takip etmek için kullanılır. Yeni bir AI oturumunda bu dosyayı okuyarak nerede kaldığımızı hatırlayabilirsiniz.

---

## Son Tamamlanan İşler (Şubat 2025)

### Lost & Found (Kayıp Eşya) – Essential Services
Pati Safe benzeri, ancak **eşyalar** için (anahtar, cüzdan, telefon, çanta vb.) bir sistem eklendi:

- **services.php** – Essential Services’e Lost & Found kartı eklendi
- **lost_found.php** – Ana liste sayfası (filtreler, abonelik, paylaşım)
- **add_lost_item.php** – İlan verme formu (login gerekli)
- **api/add_lost_item.php** – İlan kaydetme, push + subscriber bildirimi
- **api/mark_lost_item_found.php** – “Bulundu” işaretleme
- **lost_items** tablosu – `lost_found.php` içinde otomatik oluşturuluyor

Kategoriler: keys, wallet, phone, bag, glasses, documents, jewelry, other

---

## Daha Önce Yapılan İşler (Özet)

- **map.php** – Responsive, dark mode, geolocation, filtre paneli, trail sidebar
- **Service worker (sw.js)** – Google Ads/analytics fetch hatalarını önlemek için 3rd party skip
- **Kaputaş Beach** – Koordinatlar `api/get_overpass_pois.php` içinde (36.2292, 29.4492)
- **Navbar** – Haptic feedback sadece navbar’da
- **map.php** – `geolocate`, `closeDrawer`, `toggleFilters`, `toggleTrails`, `filterMap` → `window` üzerinde expose edildi

---

## Önemli Dosya Yolları

| Ne | Nerede |
|----|--------|
| Services Essential cards | `services.php` ~96–122 |
| Pati Safe (referans) | `pati_safe.php`, `add_lost_pet.php` |
| Lost & Found | `lost_found.php`, `add_lost_item.php` |
| Dil dosyaları | `lang/en.php`, `lang/tr.php` |
| API örnekleri | `api/add_lost_pet.php`, `api/subscribe.php` |

---

## SEO (Şubat 2025)

- **seo_tags.php** – Kalkan nöbetçi eczane, Kalkan guide, Kalkan lost pets için optimize
- Hedef: `Kalkan nöbetçi eczane`, `Kaş nöbetçi eczane`, `Kalkan guide`, `Kalkan rehber`, `Kalkan lost pets`, `Kalkan kayıp hayvan`, `Kalkan kayıp eşya`
- Sayfalar: duty_pharmacy, pati_safe, services, lost_found, first_aid, guidebook, index
- JSON-LD, canonical URL, og/twitter meta

---

## UI Kuralları (ÖNEMLİ – ASLA İHLAL ETME)

**Butonlar:** Asla beyaz veya açık gri buton kullanma (`bg-white`, `bg-slate-50`, `bg-slate-100`). Kontrast zayıf olur, metin okunmaz.

- **Seçili olmayan filtre/sekme butonları:** En az `bg-slate-200` veya renkli arka plan (`bg-violet-100`, `bg-pink-100` vb.) kullan. `bg-slate-100` YASAK.
- **Ana CTA butonları (Etkinlik Ekle, Gönder vb.):** Her zaman net renkli – `bg-violet-600`, `bg-blue-600` veya gradient, `text-white`. Soluk/beyaz görünmesin.
- **İkincil butonlar:** `bg-slate-200 text-slate-800` veya `bg-slate-700 text-white` – belirgin olsun.
- **Hero/başlık ikon kutuları:** Asla beyaz veya açık pastel. `bg-violet-600`, `bg-blue-600` gibi solid renk kullan.

---

## Yapılacaklar (Olası)

- [ ] İstek varsa: Lost & Found için poster generator (Pati Safe’teki gibi)
- [ ] İstek varsa: `lost_found?id=X` ile direkt ilana scroll

---

*Son güncelleme: 11 Şubat 2025*
