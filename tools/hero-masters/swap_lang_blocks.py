#!/usr/bin/env python3
"""Swap the retired HERO CINEMA lang block for the new hero keys in all locales."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

BLOCKS = {
    "en": """    /* HERO - cinematic category showcase */
    'hero_kicker' => 'Architectural Sanitary Ware',
    'hero_title_1' => 'Crafting',
    'hero_title_2' => 'Water.',
    'hero_title_3' => 'Shaping',
    'hero_title_4' => 'Spaces.',
    'hero_desc' => 'Swiss-engineered faucets, showers and intelligent water systems, sculpted from certified materials for architecture that demands silence, precision and beauty.',
    'hero_cta_primary' => 'Explore Collections',
    'hero_cta_story' => 'Watch Our Story',
    'hero_scroll' => 'Scroll to Explore',
    'hero_tabs_label' => 'Collections',
    'hero_tab_bathroom' => 'Bathroom',
    'hero_tab_kitchen' => 'Kitchen',
    'hero_tab_shower' => 'Shower',
    'hero_tab_accessories' => 'Accessories',
    'hero_tab_smart' => 'Smart',
    'hero_tab_go' => 'Open the :name collection',
    'hero_trust_1_title' => 'Premium Materials',
    'hero_trust_1_sub' => 'Certified European Brass',
    'hero_trust_2_title' => 'Swiss Engineering',
    'hero_trust_2_sub' => 'Kerox Ceramic Cores',
    'hero_trust_3_title' => 'Sustainable Water Tech',
    'hero_trust_3_sub' => '45% Water Conservation',
    'hero_trust_4_title' => '25 Years Warranty',
    'hero_trust_4_sub' => 'Factory Replacement Policy',
""",
    "tr": """    /* HERO - sinematik koleksiyon gösterimi */
    'hero_kicker' => 'Mimari Sıhhi Tesisat',
    'hero_title_1' => 'Suyu',
    'hero_title_2' => 'Şekillendiriyoruz.',
    'hero_title_3' => 'Mekânları',
    'hero_title_4' => 'Dönüştürüyoruz.',
    'hero_desc' => 'İsviçre mühendisliğiyle geliştirilen bataryalar, duş sistemleri ve akıllı su teknolojileri; sessizlik, hassasiyet ve güzellik talep eden mimari için sertifikalı malzemelerden şekillendirildi.',
    'hero_cta_primary' => 'Koleksiyonları Keşfet',
    'hero_cta_story' => 'Hikâyemizi İzleyin',
    'hero_scroll' => 'Keşfetmek İçin Kaydır',
    'hero_tabs_label' => 'Koleksiyonlar',
    'hero_tab_bathroom' => 'Banyo',
    'hero_tab_kitchen' => 'Mutfak',
    'hero_tab_shower' => 'Duş',
    'hero_tab_accessories' => 'Aksesuarlar',
    'hero_tab_smart' => 'Akıllı',
    'hero_tab_go' => ':name koleksiyonunu aç',
    'hero_trust_1_title' => 'Premium Malzemeler',
    'hero_trust_1_sub' => 'Sertifikalı Avrupa Pirinci',
    'hero_trust_2_title' => 'İsviçre Mühendisliği',
    'hero_trust_2_sub' => 'Kerox Seramik Kartuşlar',
    'hero_trust_3_title' => 'Sürdürülebilir Su Teknolojisi',
    'hero_trust_3_sub' => '%45 Su Tasarrufu',
    'hero_trust_4_title' => '25 Yıl Garanti',
    'hero_trust_4_sub' => 'Fabrika Değişim Güvencesi',
""",
    "cs": """    /* HERO - filmové představení kolekcí */
    'hero_kicker' => 'Architektonické sanitární vybavení',
    'hero_title_1' => 'Tvoříme',
    'hero_title_2' => 'vodu.',
    'hero_title_3' => 'Utváříme',
    'hero_title_4' => 'prostor.',
    'hero_desc' => 'Kohoutky, sprchové systémy a inteligentní vodní technologie se švýcarským inženýrstvím — vytvořené z certifikovaných materiálů pro architekturu, která vyžaduje ticho, preciznost a krásu.',
    'hero_cta_primary' => 'Prozkoumat kolekce',
    'hero_cta_story' => 'Přehrát náš příběh',
    'hero_scroll' => 'Posunujte se a objevujte',
    'hero_tabs_label' => 'Kolekce',
    'hero_tab_bathroom' => 'Koupelna',
    'hero_tab_kitchen' => 'Kuchyně',
    'hero_tab_shower' => 'Sprcha',
    'hero_tab_accessories' => 'Příslušenství',
    'hero_tab_smart' => 'Chytré',
    'hero_tab_go' => 'Otevřít kolekci :name',
    'hero_trust_1_title' => 'Prémiové materiály',
    'hero_trust_1_sub' => 'Certifikovaný evropský mosaz',
    'hero_trust_2_title' => 'Švýcarské inženýrství',
    'hero_trust_2_sub' => 'Keramické kartuše Kerox',
    'hero_trust_3_title' => 'Udržitelné technologie',
    'hero_trust_3_sub' => 'Úspora vody 45 %',
    'hero_trust_4_title' => 'Záruka 25 let',
    'hero_trust_4_sub' => 'Záruka výměny z továrny',
""",
}

pattern = re.compile(
    r"    /\* HERO CINEMA \*/.*?'cinema_primary' => '[^']*',\n",
    re.S,
)

for locale, block in BLOCKS.items():
    path = ROOT / f"resources/lang/{locale}/home.php"
    src = path.read_text(encoding="utf-8")
    patched, n = pattern.subn(block, src)
    if n != 1:
        raise SystemExit(f"{path}: expected exactly one HERO CINEMA block, found {n}")
    path.write_text(patched, encoding="utf-8")
    print(f"{locale}: hero keys installed")
