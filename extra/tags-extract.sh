#!/bin/bash
# URL
grep -hR tag *rules/*.conf |sed 's/cve,/cve;/g' |sed 's/bugtraq,/bugtraq;/g' | sed 's/url,/url;/g' |sed 's/,/\n/g' |grep tag: |sed 's/tag://' |sed 's/"$//' |sort -u |sed "s/'//g" |sed "s/;/,/g" |grep url |awk -F, '{ print "NULL;"$0";http://"$2";NULL;NULL"}' > $1
# URL2
grep -hR tag *rules/*.conf |sed 's/cve,/cve;/g' |sed 's/bugtraq,/bugtraq;/g' | sed 's/url,/url;/g' |sed 's/,/\n/g' |grep tag: |sed 's/tag://' |sed 's/"$//' |sort -u |sed "s/'//g" |sed "s/;/,/g" |grep -P "https?://" |awk '{ print "NULL;"$0";"$0";NULL;NULL"}' >> $1
# Bugtraq
grep -hR tag *rules/*.conf |sed 's/cve,/cve;/g' |sed 's/bugtraq,/bugtraq;/g' | sed 's/url,/url;/g' |sed 's/,/\n/g' |grep tag: |sed 's/tag://' |sed 's/"$//' |sort -u |sed "s/'//g" |sed "s/;/,/g" |grep -P "bugtraq" |awk -F, '{ print "NULL;"$0";http://www.securityfocus.com/bid/"$2";NULL;NULL"}' >> $1
# CVE
grep -hR tag *rules/*.conf |sed 's/cve,/cve;/g' |sed 's/bugtraq,/bugtraq;/g' | sed 's/url,/url;/g' |sed 's/,/\n/g' |grep tag: |sed 's/tag://' |sed 's/"$//' |sort -u |sed "s/'//g" |sed "s/;/,/g" |grep -iP "cve" |awk -F, '{ print "NULL;"$0";http://cve.mitre.org/cgi-bin/cvename.cgi?name="$2";NULL;NULL"}'>> $1

# Others
grep -hR tag *rules/*.conf |sed 's/cve,/cve;/g' |sed 's/bugtraq,/bugtraq;/g' | sed 's/url,/url;/g' |sed 's/,/\n/g' |grep tag: |sed 's/tag://' |sed 's/"$//' |sort -u |sed "s/'//g" |sed "s/;/,/g" |grep -viP "(^https?://|^url|^bugtraq|cve)" |awk -F, '{ print "NULL;"$0";"$0";NULL;NULL"}' >> $1
