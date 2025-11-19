import json
import os

# Define the new keys
new_keys = {
    "landing": {
        "hero": {
            "badge": "New: AI-Powered Expense Tracking",
            "title": "Accounting that thinks for you.",
            "subtitle": "Facturino combines powerful invoicing with advanced AI to automate your bookkeeping, track expenses, and predict your financial future.",
            "cta_primary": "Start Free Trial",
            "cta_secondary": "See How It Works",
            "dashboard_alt": "Facturino Dashboard"
        },
        "partners": {
            "trusted_by": "Trusted by forward-thinking companies"
        },
        "features": {
            "ai_insights": {
                "title": "AI-Powered Financial Insights",
                "description": "Get real-time suggestions on how to save money and optimize your cash flow."
            },
            "invoicing": {
                "title": "Beautiful Invoices",
                "description": "Create professional invoices in seconds that get you paid faster."
            },
            "expenses": {
                "title": "Smart Expense Tracking",
                "description": "Snap a photo of your receipt and let our AI extract the data automatically."
            },
            "reporting": {
                "title": "Comprehensive Reporting",
                "description": "Visualize your financial health with interactive charts and reports."
            }
        },
        "how_it_works": {
            "title": "How Facturino Works",
            "subtitle": "Get started in minutes, not days.",
            "step1": {
                "title": "Create Account",
                "description": "Sign up for free and set up your company profile."
            },
            "step2": {
                "title": "Connect Data",
                "description": "Import your customers and connect your bank accounts."
            },
            "step3": {
                "title": "Get Paid",
                "description": "Send invoices and track payments automatically."
            }
        },
        "testimonials": {
            "title": "Loved by businesses like yours",
            "review1": "Facturino has completely transformed how we manage our finances. The AI insights are a game changer.",
            "author1": "Elena M.",
            "role1": "CEO at TechStart",
            "review2": "The best invoicing software I've used. Simple, fast, and beautiful.",
            "author2": "Marko P.",
            "role2": "Freelance Designer",
            "review3": "I save hours every week on bookkeeping thanks to the automated expense tracking.",
            "author3": "Sarah J.",
            "role3": "Owner at Cafe Bliss"
        },
        "faq": {
            "title": "Frequently Asked Questions",
            "q1": "Is there a free trial?",
            "a1": "Yes, we offer a 14-day free trial on all paid plans.",
            "q2": "Can I cancel anytime?",
            "a2": "Absolutely. There are no long-term contracts and you can cancel whenever you like.",
            "q3": "Is my data secure?",
            "a3": "We use bank-level encryption to ensure your data is always safe and secure.",
            "q4": "Do you support multiple currencies?",
            "a4": "Yes, Facturino supports over 100 currencies with live exchange rates."
        },
        "cta_footer": {
            "title": "Ready to streamline your accounting?",
            "subtitle": "Join thousands of businesses trusting Facturino.",
            "button": "Get Started Now"
        },
        "footer": {
            "product": "Product",
            "features": "Features",
            "pricing": "Pricing",
            "company": "Company",
            "about": "About Us",
            "blog": "Blog",
            "careers": "Careers",
            "legal": "Legal",
            "privacy": "Privacy Policy",
            "terms": "Terms of Service",
            "rights": "All rights reserved."
        }
    },
    "pricing_public": {
        "title": "Simple, Transparent Pricing",
        "subtitle": "Choose the plan that fits your business needs.",
        "monthly": "Monthly",
        "yearly": "Yearly",
        "save_text": "Save 20%",
        "most_popular": "Most Popular",
        "per_month": "/mo",
        "per_year": "/yr",
        "start_trial": "Start Free Trial",
        "get_started": "Get Started",
        "contact_sales": "Contact Sales",
        "features_label": "Everything in {prev} plus:",
        "custom_solution": {
            "title": "Need a custom solution?",
            "description": "Contact us for enterprise pricing and custom integrations.",
            "button": "Contact Sales"
        },
        "plans": {
            "free": {
                "name": "Free",
                "description": "Perfect for freelancers just starting out."
            },
            "starter": {
                "name": "Starter",
                "description": "Great for small businesses growing fast."
            },
            "standard": {
                "name": "Standard",
                "description": "For established businesses with teams."
            },
            "business": {
                "name": "Business",
                "description": "Advanced features for scaling companies."
            }
        }
    }
}

def add_suffix(data, suffix):
    if isinstance(data, dict):
        return {k: add_suffix(v, suffix) for k, v in data.items()}
    elif isinstance(data, str):
        return f"{data} {suffix}"
    else:
        return data

def update_json(file_path, suffix=""):
    try:
        with open(file_path, 'r') as f:
            data = json.load(f)
        
        keys_to_add = new_keys
        if suffix:
            keys_to_add = add_suffix(new_keys, suffix)
            
        data.update(keys_to_add)
        
        with open(file_path, 'w') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"Updated {file_path}")
    except Exception as e:
        print(f"Error updating {file_path}: {e}")

base_path = "/Users/tamsar/Downloads/mkaccounting/lang"
update_json(os.path.join(base_path, "en.json"))
update_json(os.path.join(base_path, "mk.json"), "(MK)")
update_json(os.path.join(base_path, "tr.json"), "(TR)")
update_json(os.path.join(base_path, "sq.json"), "(SQ)")
