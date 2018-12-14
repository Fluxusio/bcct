# Tavolos Backend

## Settings Management

Settings that are common for every environment:
- `/sites/default/settings.php`
- `/sites/default/services.yml`

Each environment can override those settings with these files:
- `/sites/default/settings.local.php`
- `/sites/default/services.local.yml`

Development environments can use the predefined templates. Follow the 
instructions in these files: 
- `/sites/default/tavolos.settings.local.php`
- `/sites/default/tavolos.services.local.yml`
 
## PHP Version

Production environment currently uses php 7.0.30.

All code and all dependencies should support that version.

---

(@todo More documentation)

