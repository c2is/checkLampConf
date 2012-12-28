checkLampConfig
===============

Check services versions, directories rights, groups definitions etc.



# Installation
Put the file checklampconf.php in the root directory of your hosted space.
Add exe right :

```bash
$ chmod +x checklampconf.php
```

# Usage

```bash
$ ./checklampconf.php
```
Adjust some vars according your needs :
$processing -> params["apache.version"] = "2.2" ;
$processing -> params["php.version"] = "5.3" ;
