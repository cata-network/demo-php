#!/usr/bin/expect  --


if { [llength $argv] != 5} {
	puts "usage: $argv0 ip port user passwd \"command \[params\]\""
	exit 1
}


set maxRetry 1
for {set retryNum 0} {$retryNum<$maxRetry} {incr retryNum} {

spawn ssh -q [lindex $argv 0] -p[lindex $argv 1] -l[lindex $argv 2] [lindex $argv 4]

set timeout 600
expect {

	"assword:" {

		send "[lindex $argv 3]\r"

		set timeout -1
		expect eof

		break

	}

 
	"yes/no)?" {
  
		send "yes\r"

		expect "assword:"

		send "[lindex $argv 3]\r"

		set timeout -1
		expect eof

		break

	}
  
	timeout {continue}
	eof {continue}

}
}

 


