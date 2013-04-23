# CHANGELOG

* 3.8.0 (2013-xx-xx)

  - SwiftMailer integration (replace PHPMailer)
    - Email now include an HTML view
    - Email can now include have subject prefix
    - Email can be sent to SMTP server using TLS encryption (only SSL was supported)
  - SphinxSearch is now stable (require SphinxSearch 2.0.6)
  - Add support for stemmatisation in Phrasea Engine
  - Add bin/setup command utility
    - Add ability to install Phraseanet command line
  - Lots of cleanup and code refactorisation
  - Add task-manager logging to syslog
  - Add bin/console mail:test command to check email configuration

* 3.7.11 (2013-04-23)

  - Enhancement : Animated Gifs (video support) does not requir Gmagick anymore to work properly.
  - BugFix : When importing users from CSV file, some properties were missing.
  - BugFix : In Report, CSV export is limited to 30 lines.
