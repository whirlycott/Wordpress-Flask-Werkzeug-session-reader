Wordpress Flask/Werkzeug secure cookie session reader
=======================================

A basic Wordpress plugin that allows read-only access to Flask/Werkzeug secure sessions.  Install 
it like a normal Wordpress plugin by putting it in wp-content/plugins/flask-session-reader.php.  There is 
an admin interface where you will specify you Flask secret key and the name of your session (which is 'session' by default in Flask).

This plugin verifies the HMAC of the session and provides you with read-only access to the contents and ensures that the cookie hasn't 
been modified with.

Currently, Flask uses Werkzeug secure cookies to serialize the data which in turn relies on Pickle. 
This is a really bad idea as it makes interop (say, with PHP) a complete pain in the neck.  Some also 
point out potential [security issues](http://stacksmashing.net/2012/08/10/dear-flask-please-fix-your-secure-cookies/) 
with the pickle approach.

For the purposes of interop, we've decided to use JSON as the serialization mechanism.  Perhaps thrift or protobuffers are your cup of tea?  Pull requests 
are welcome!  For now, JSON is fine for us.  In order to do that, you have to make some small changes to your Flask installation.  First, put this somewhere in your Python code:

<pre>
from werkzeug.contrib.securecookie import SecureCookie
from flask.sessions import SessionMixin, SessionInterface, SecureCookieSessionInterface
import json

class JsonSecureCookie(SecureCookie): 
	serialization_method = json

class JsonSecureCookieSession(JsonSecureCookie, SessionMixin):
	pass

class JsonSecureCookieSessionInterface(SecureCookieSessionInterface):
	session_class = JsonSecureCookieSession
</pre>

Next, tell Flask to use JsonSecureCookieSessionInterface as the session class:

<pre>
from flask_application.cookie import JsonSecureCookieSessionInterface
from flask import Flask
app = Flask(__name__)
app.session_interface = JsonSecureCookieSessionInterface()
</pre>

That's it.  Comments and questions welcome.

phil. <phil@whirlycott.com>