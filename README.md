WAF-FLE - Readme
===================

Web Application Firewall: Fast Log and Event Console

   Copyright (C) 2011 - 2014  Klaubert Herr 
   For new (released) versions, check at http://waf-fle.org
   WAF-FLE is relased under GPL v2 License

SUMMARY:
---------
WAF-FLE is a OpenSource ModSecurity Console, allows modsecurity admin
to store, view and search events sent by sensors using a graphical 
dashboard to drill-down and find quickly the most relevant events. It
is designed to be fast and flexible, while keeping a powerful and easy
to use filter, with almost all fields clickable to use on filter.

Features
 * Central event console
 * Support Modsecurity in “traditional” and “Anomaly Scoring”
 * Brings mlog2waffle as a replacement to mlogc
 * Receive events using mlog2waffle or mlogc
  * mlog2waffle: in real-time, following log tail, or batch scheduled in crontab
  * mlogc: in real-time, piped with ModSecurity log, in batch scheduled in crontab
 * No sensor limit
 * Drill down of events with filter
 * Dashboard with recent events information
 * Almost every event data and charts are “clickable” deepening the drill down filter
 * Inverted filter (to filter for “all but this item”)
 * Filter for network (in CIDR format, x.x.x.x/22)
 * Original format (Raw) to event download
 * Use Mysql as database
 * Wizard to help configure log feed between ModSecurity sensors and WAF-FLE
 * Open Source released under GPL v2
