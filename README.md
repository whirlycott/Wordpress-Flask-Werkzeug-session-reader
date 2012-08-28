Wordpress-Flask-Werkzeug-session-reader
=======================================

A basic Wordpress plugin that allows read-only access to Flask/Werkzeug secure sessions.  Install 
it like a normal Wordpress plugin by putting it in wp-content/plugins/flask-session-reader.php.  There is 
currently no admin interface, so you have to modify the file in order to specify your Flask secret key.

This plugin verifies the HMAC of the session and provides you with read-only access to the contents.  

Currently, Flask uses Werkzeug secure cookies to serialize the data which in turn relies on Pickle. 
This is a really bad idea as it makes interop (say, with PHP) a complete pain in the neck.  Some also 
point out potential [security issues](http://stacksmashing.net/2012/08/10/dear-flask-please-fix-your-secure-cookies/) 
with the pickle approach.

For the purposes of interop, we've decided to use JSON as the serialization mechanism.  Perhaps thrift or protobuffers are your cup of tea?  Pull requests 
are welcome.  For now, JSON is fine for us.



