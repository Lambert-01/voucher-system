# Voucher Card Generation - Visual Guide

## What You Have Now 🎉

Your N.HONEST Voucher System can generate professional ATM-style cards!

```
┌─────────────────────────────────────────────────────────────┐
│                    CARD GENERATION SYSTEM                    │
└─────────────────────────────────────────────────────────────┘

Your Templates + Voucher Data = Generated Cards
   (side1.png)      (from database)     (ready to print)
   (side2.png)
```

## How It Works

### Step 1: Template Files
```
/voucher card/
├── side1.png  ← Your professional FRONT design
└── side2.png  ← Your professional BACK design
```

### Step 2: Voucher Data (from database)
```
- Voucher Number: HSV-2026-0001
- Client Name: ALLELUIA ALAIN
- EVA ID: PX-Q3-2026-CB80
- Amount: 436,800 RWF
- QR Code: [generated]
```

### Step 3: Generated Card
```
System overlays data on templates → Saves as PNG

/public/assets/cards/
├── voucher_1_front.png  ✅ Ready to print
└── voucher_1_back.png   ✅ Ready to print
```

## Visual Process Flow

```
┌───────────┐       ┌──────────┐       ┌───────────┐
│  Admin    │───→   │  System  │───→   │ Generated │
│  Clicks   │       │ Overlays │       │   Cards   │
│ "Generate"│       │   Data   │       │  (PNG)    │
└───────────┘       └──────────┘       └───────────┘
      │                   │                   │
      │                   │                   │
      ▼                   ▼                   ▼
Select Batch      + Template Images    Print-Ready
(e.g., Q3-2026)   + Voucher Data      ATM-Style Cards
                  + QR Codes
```

## Card Layout Example

### FRONT CARD (side1.png + data)
```
┌─────────────────────────────────────────┐
│  [N.HONEST LOGO]                        │
│                                         │
│  HSV-2026-0001                         │ ← Voucher #
│                                         │
│  ALLELUIA ALAIN                        │ ← Client Name
│  PX-Q3-2026-CB80                       │ ← EVA ID
│                                         │
│  ┌─────────────────┐                   │
│  │  436,800 RWF   │                   │ ← Amount
│  └─────────────────┘                   │
│                                         │
│  Kisimenti • 0788633739                │
└─────────────────────────────────────────┘
```

### BACK CARD (side2.png + QR)
```
┌─────────────────────────────────────────┐
│  N.HONEST                               │
│  Scan to load voucher data              │
│                                         │
│  ┌─────┐  • Valid at N.HONEST only     │
│  │ QR  │  • Present at cashier         │
│  │CODE │  • Balance tracked in system  │
│  └─────┘  • Report if lost/stolen      │
│            • Non-transferable           │
│                                         │
│  Contact: 0788633739                    │
└─────────────────────────────────────────┘
```

## User Journey

### For Admin
```
1. Login as Admin
2. Navigate to "Generate Cards"
3. Select batch from dropdown
4. Click "Generate Cards"
5. System creates cards for all vouchers
6. Cards saved to /public/assets/cards/
7. Ready to print or distribute
```

### For Individual Card
```
1. Go to Admin → Vouchers
2. Find voucher (e.g., HSV-2026-0001)
3. Click "View"
4. Click "Print Card"
5. Card auto-generates
6. Print dialog opens
7. Print front and back
```

## File Organization

```
YOUR SYSTEM
│
├── 📂 voucher card/              (Your Templates)
│   ├── 🎨 side1.png              Front template
│   ├── 🎨 side2.png              Back template
│   └── 📸 [examples].png         Sample cards
│
├── 📂 app/Helpers/               (Generation Logic)
│   └── 🛠️ card.php               Overlays data on templates
│
├── 📂 public/admin/              (Admin Pages)
│   ├── 📄 generate-cards.php     Bulk generation UI
│   ├── 📄 generate-card.php      Single card API
│   ├── 📄 test-cards.php         Testing & diagnostics
│   ├── 📄 print-card.php         Print interface
│   └── 📄 card-coordinate-helper.html  Position tool
│
├── 📂 public/assets/cards/       (Generated Cards)
│   ├── 🎫 voucher_1_front.png
│   ├── 🎫 voucher_1_back.png
│   └── ...
│
└── 📚 Documentation
    ├── CARD_GENERATION.md         Full technical docs
    ├── CARD_QUICKSTART.md         Quick start guide
    └── IMPLEMENTATION_SUMMARY.md  What was created
```

## Quick Start Checklist

```
☐ 1. Run setup script
      ./setup-cards.sh

☐ 2. Visit test page
      http://localhost:8000/admin/test-cards.php

☐ 3. Generate test card
      Click "Generate Test Card"

☐ 4. Verify output
      Check if card looks good

☐ 5. Adjust if needed
      Use card-coordinate-helper.html

☐ 6. Generate real cards
      http://localhost:8000/admin/generate-cards.php

☐ 7. Print and distribute
      Cards ready at /public/assets/cards/
```

## What Makes This Special

✨ **Professional Design**: Uses your actual templates
✨ **ATM Card Size**: Standard 85.6mm × 53.98mm
✨ **Print Ready**: High resolution, proper dimensions
✨ **Bulk Generation**: Create hundreds of cards at once
✨ **QR Integration**: Automatic QR code placement
✨ **Easy Customization**: Visual positioning tool included
✨ **Complete System**: From generation to printing

## URLs to Know

```
🧪 Testing:
   http://localhost:8000/admin/test-cards.php

📦 Bulk Generation:
   http://localhost:8000/admin/generate-cards.php

🎯 Positioning Helper:
   http://localhost:8000/admin/card-coordinate-helper.html

🖨️ Print Single Card:
   http://localhost:8000/admin/print-card.php?id=1
```

## Common Tasks

### Generate cards for new batch
```
1. Import vouchers → CSV upload
2. Go to Generate Cards
3. Select the new batch
4. Click Generate
5. Done! Cards ready in /assets/cards/
```

### Print single card
```
1. Admin → Vouchers
2. Find voucher
3. Click "Print Card"
4. Print dialog opens
5. Print both sides
```

### Adjust text position
```
1. Open card-coordinate-helper.html
2. Drag red markers
3. Copy generated PHP code
4. Update app/Helpers/card.php
5. Regenerate cards
```

## Support

📖 **Documentation**: See CARD_GENERATION.md
🚀 **Quick Guide**: See CARD_QUICKSTART.md  
🔧 **Setup**: Run ./setup-cards.sh
📞 **Help**: 0788633739

---

**Your system is ready to generate professional voucher cards!** 🎉

Start with the test page, then generate cards for your batches.
```
