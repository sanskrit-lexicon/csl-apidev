orphus = (function () {
  var leftSelTag      = "<!!!>";
  var rightSelTag     = "<!!!>";
  // Controls how many characters to the left and right of selected text
  // would be included in the error report.
  var marginLength    = 80;
  // Specifies the maximum amount of characters sent, together with margins
  // and left/rightSelTag-s. I haven't checked whether Orphus server would
  // be willing to receive more than 256 characters.
  var maxReportLength = 256;
  var messageTable    = {
    badbrowser: "Your browser does not support selection handling or IFRAMEs. Probably you're using an obsolete browser.",
    thanks: "Thanks!",
    subject: "Mistake report",
    docmsg: "Document:",
    intextmsg: "Mistake in text:",
    name: "Orphus system",
    to: "Orphus user",
    send: "Send",
    cancel: "Cancel",
    enterEmail: "Your email or name (optional):",
    entercmnt: "Correction/comment:"
  };
  var correctionParams = {};
  var correctionUrl = '';
  var nonMistakes = '';
  var w = window;
  var d = w.document;
  var de = d.documentElement;
  var b = d.body;
  var modalWindow = {};
  var _11 = false;
  var _13 = function () {
    d.onkeypress = _17;
  };
  var showPseudoForm = function (e) {
    e.style.position = "absolute";
    e.style.top = "-10000px";
    if (b.lastChild) {
      b.insertBefore(e, b.lastChild);
    } else {
      b.appendChild(e);
    }
  };
  var sendValues = function (url, selection, formValues) {
    var xhttp = new XMLHttpRequest();

    var mistakeBlock = removeNewlines(selection.pre + leftSelTag + selection.text + rightSelTag + selection.suf);

    /*var mistakeBlock =
      removeNewlines(leftSelTag +
        selection.text + rightSelTag);*/
    var valuesToSend = correctionParams;
    valuesToSend.entry_old = mistakeBlock;
    valuesToSend.entry_new = formValues.entry_comment;
    valuesToSend.entry_email = formValues.entry_email;

    if (valuesToSend.entry_email) {
      setCookie('email', valuesToSend.entry_email);
    }

    if (correctionUrl && valuesToSend.entry_new.length > 0) {
      var bodyParts = [];

      for (var code in valuesToSend) {
        if (valuesToSend.hasOwnProperty(code)) {
          var value = valuesToSend[code];
          bodyParts.push(code + '=' + value);
        }
      }

      var body = bodyParts.join('&');

      //console.log('body to send', bodyParts, body);
      xhttp.open("POST", correctionUrl, true);
      xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhttp.onreadystatechange = function() {};
      xhttp.send(body);
    } else if (!valuesToSend.entry_new || valuesToSend.entry_new.length === 0) {
      alert('Correction field shouldn\'t be empty');
      return false;
    }
  };
  var _29 = function () {
    var _2a = 0,
      _2b = 0;
    if (typeof (w.innerWidth) === "number") {
      _2a = w.innerWidth;
      _2b = w.innerHeight;
    } else {
      if (de && (de.clientWidth || de.clientHeight)) {
        _2a = de.clientWidth;
        _2b = de.clientHeight;
      } else {
        if (b && (b.clientWidth || b.clientHeight)) {
          _2a = b.clientWidth;
          _2b = b.clientHeight;
        }
      }
    }
    var _2c = 0,
      _2d = 0;
    if (typeof (w.pageYOffset) === "number") {
      _2d = w.pageYOffset;
      _2c = w.pageXOffset;
    } else {
      if (b && (b.scrollLeft || b.scrollTop)) {
        _2d = b.scrollTop;
        _2c = b.scrollLeft;
      } else {
        if (de && (de.scrollLeft || de.scrollTop)) {
          _2d = de.scrollTop;
          _2c = de.scrollLeft;
        }
      }
    }
    return {
      w: _2a,
      h: _2b,
      x: _2c,
      y: _2d
    };
  };
  modalWindow.confirm = function (_2e, _2f, _30) {
    var ts = new Date().getTime();
    var _32 = confirm(messageTable.docmsg + "\n   " +
      d.location.href + "\n" +
      messageTable.intextmsg + "\n   \"" +
      _2e + "\"\n\n");
    var dt = new Date().getTime() - ts;
    if (_32) {
      _2f("");
    } else {
      if (!_30 && dt < 50) {
        var sv = d.onkeyup;
        d.onkeyup = function (e) {
          if (!e) {
            e = window.event;
          }
          if (e.keyCode == 17) {
            d.onkeyup = sv;
            modalWindow.confirm(_2e, _2f, true);
          }
        };
      }
    }
  };
  modalWindow.css = function (_36, processCallback) {
    if (_11) {
      return;
    }
    _11 = true;
    var div = d.createElement("DIV");
    var w = 550;
    if (w > b.clientWidth - 10) {
      w = b.clientWidth - 10;
    }
    div.style.zIndex = "10001";

    var wrapDiv = function (style, inner) {
      if (style === "")
      { return "<div>" + inner + "</div>"; }
      else
      { return "<div style=\"" + style + "\">" + inner + "</div>"; }
    };

    var nonMistakesBox = "";
    if (nonMistakes !== "") {
      nonMistakesBox =
        wrapDiv("font-size:0.5em; line-height:100%;" +
          "width:50%; margin:1em auto; padding:0.5em 0 0 0.5em;" +
          "border:1px solid red",
          "Non-mistakes: " + nonMistakes); }

    var leftTagRepl = "<span style=\"background-color:#ff7373\">";
    var rightTagRepl = "</span>";

    var displayMistake =
      wrapDiv("font-weight:bold;padding-bottom:0.2em",
        messageTable.intextmsg) +
      wrapDiv("padding:0 0 1em 1em",
        _36.replace(leftSelTag,  leftTagRepl)
          .replace(rightSelTag, rightTagRepl));

    var cookieEmailValue = getCookie('email') || '';
    var buttons =
      wrapDiv("text-align:right",
        "<input type=\"button\" value=\"" + messageTable.send +
        "\" style=\"width:7em;font-weight:bold\">&nbsp;" +
        "<input type=\"button\" value=\"" + messageTable.cancel +
        "\" style=\"width:5em\">");

    var commentForm =
      "<form style=\"padding:0;margin:0;border:0\">" +
      wrapDiv("", messageTable.entercmnt) +
      "<input id='comment' name='comment' maxlength='250' style='width:100%;margin:0.2em 0'/>" +
      wrapDiv("padding-bottom:1em", "") +
      wrapDiv("", messageTable.enterEmail) +
      "<input id='email' name='email' type='text' value='" + cookieEmailValue +
      "' maxlength='250' style='width:100%;margin:0.2em 0' />" +
      wrapDiv("padding-bottom:1em", "") +
      buttons +
      "</form>";

    div.innerHTML =
      wrapDiv(
        "background-color:#eee;" + "width:" + w + "px;" +
        "z-index:10001;" + "border: 1px solid #555;" +
        "padding:1em;" + "font-size: 90%;" + "color:black",
        "<hr>" +
        nonMistakesBox +
        displayMistake +
        wrapDiv("padding-bottom:1em", "") +
        commentForm);

    showPseudoForm(div);
    var commentElement = div.getElementsByTagName("input");
    var mainForm = div.getElementsByTagName("form");
    var _3d = null;
    var _3e = [];
    var closeModal = function () {
      d.onkeydown = _3d;
      _3d = null;
      div.parentNode.removeChild(div);
      for (var i = 0; i < _3e.length; i++) {
        _3e[i][0].style.visibility = _3e[i][1];
      }
      _11 = false;
    };
    var pos = function (p) {
      var s = {
        x: 0,
        y: 0
      };
      while (p.offsetParent) {
        s.x += p.offsetLeft;
        s.y += p.offsetTop;
        p = p.offsetParent;
      }
      return s;
    };
    setTimeout(function () {
      var w = div.clientWidth;
      var h = div.clientHeight;
      var dim = _29();
      var x = (dim.w - w) / 2 + dim.x;
      if (x < 10) {
        x = 10;
      }
      var y = (dim.h - h) / 2 + dim.y - 10;
      if (y < 10) {
        y = 10;
      }
      div.style.left = x + "px";
      div.style.top = y + "px";
      if (navigator.userAgent.match(/MSIE (\d+)/) && RegExp.$1 < 7) {
        var _49 = d.getElementsByTagName("SELECT");
        for (var i = 0; i < _49.length; i++) {
          var s = _49[i];
          var p = pos(s);
          if (p.x > x + w
            || p.y > y + h
            || p.x + s.offsetWidth < x
            || p.y + s.offsetHeight < y) {
            continue; }
          _3e[_3e.length] = [s, s.style.visibility];
          s.style.visibility = "hidden";
        }
      }
      _3d = d.onkeydown;
      d.onkeydown = function (e) {
        if (!e) {
          e = window.event;
        }
        if (e.keyCode === 27) {
          closeModal();
        }
      };
      commentElement[commentElement.length - 2].onclick = function () {
        var result = processCallback({
          entry_comment: commentElement.comment.value,
          entry_email: commentElement.email.value
        });
        if (result !== false) {
          closeModal();
        }
        return false;
      };
      commentElement[commentElement.length - 1].onclick = function () {
        closeModal();
      };
    }, 10);
  };
  var removeNewlines = function (str) {
    return ("" + str).replace(/[\r\n]+/g, " ")
      .replace(/^\s+|\s+$/g, "");
  };
  var getSelectedText = function () {
    try {
      var _51 = null;
      var _52 = null;
      if (w.getSelection) {
        _52 = w.getSelection();
      } else {
        if (d.getSelection) {
          _52 = d.getSelection();
        } else {
          _52 = d.selection;
        }
      }
      if (_52 != null) {
        var pre = "",
          _51 = null,
          suf = "",
          pos = -1;
        if (_52.getRangeAt) {
          var r = _52.getRangeAt(0);
          _51 = r.toString();
          var _58 = d.createRange();
          _58.setStartBefore(r.startContainer.ownerDocument.body);
          _58.setEnd(r.startContainer, r.startOffset);
          pre = _58.toString();
          var _59 = r.cloneRange();
          _59.setStart(r.endContainer, r.endOffset);
          _59.setEndAfter(r.endContainer.ownerDocument.body);
          suf = _59.toString();
        } else {
          if (_52.createRange) {
            var r = _52.createRange();
            _51 = r.text;
            var _58 = _52.createRange();
            _58.moveStart("character", -marginLength);
            _58.moveEnd("character", -_51.length);
            pre = _58.text;
            var _59 = _52.createRange();
            _59.moveEnd("character", marginLength);
            _59.moveStart("character", _51.length);
            suf = _59.text;
          } else {
            _51 = "" + _52;
          }
        }
        var p;
        var s = (p = _51.match(/^(\s*)/)) && p[0].length;
        var e = (p = _51.match(/(\s*)$/)) && p[0].length;
        pre = pre + _51.substring(0, s);
        suf = _51.substring(_51.length - e, _51.length) + suf;
        _51 = _51.substring(s, _51.length - e);
        if (_51 == "") {
          return null;
        }
        return {
          pre: pre,
          text: _51,
          suf: suf,
          pos: pos
        };
      } else {
        alert(messageTable.badbrowser);
        return;
      }
    } catch (e) {
      return null;
    }
  };
  var reportSelected = function () {
    if (navigator.appName.indexOf("Netscape") != -1
      && eval(navigator.appVersion.substring(0, 1)) < 5) {
      alert(messageTable.badbrowser);
      return;
    }
    var selection = getSelectedText();
    if (!selection) {
      return;
    }
    with(selection) {
      pre = pre.substring(pre.length - marginLength, pre.length)
        .replace(/^\S{1,10}\s+/, "");
      suf = suf.substring(0, marginLength)
        .replace(/\s+\S{1,10}$/, "");
    }
    var framingLength =
      selection.pre.length + selection.suf.length +
      leftSelTag.length + rightSelTag.length;
    // If the report turns out to be is too long, we replace some text in
    // the middle of selection with “[...]”.
    if (framingLength + selection.text.length > maxReportLength) {
      var allowed = (maxReportLength-framingLength-5)/2;
      selection.text =
        selection.text.slice(0, allowed) + "[...]" +
        selection.text.slice(-allowed);
    }
    var mistakeBlock =
      removeNewlines(selection.pre + leftSelTag +
        selection.text + rightSelTag + selection.suf);
    modalWindow.css(mistakeBlock, function (formValues) {
      return sendValues(d.location.href, selection, formValues);
    });
  };
  var initialization = function (object) {
    if (object) {
      correctionUrl = object.correctionsUrl || '';
      correctionParams = object.params || {};
    }
  };
  var _17 = function (e) {
     //b = d.body;  
    var comboPressed = 0;
    var we = w.event;
    if (we) {
      comboPressed =  we.keyCode == 10
        || (we.keyCode == 13 && we.ctrlKey);
    } else {
      if (e) {
        comboPressed = (e.which == 10 && e.modifiers == 2)
          || (e.keyCode == 0 && e.charCode == 106 && e.ctrlKey)
          || (e.keyCode == 13 && e.ctrlKey);
      }
    } if (comboPressed) {
      reportSelected();
      return false;
    }
  };
  var setCookie = function (name, value, options) {
    options = options || {};
    var d = new Date();
    d.setTime(d.getTime() + (2 * (3600 * 24 * 365) * 1000));
    var expires = d;
    if (expires && expires.toUTCString) {
      options.expires = expires.toUTCString();
    }
    value = encodeURIComponent(value);
    var updatedCookie = name + "=" + value;
    for (var propName in options) {
      updatedCookie += "; " + propName;
      var propValue = options[propName];
      if (propValue !== true) {
        updatedCookie += "=" + propValue;
      }
    }
    document.cookie = updatedCookie;
  };
  var getCookie = function(name) {
    var matches = document.cookie.match(new RegExp(
      "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
  };
  _13();
  return {
    init: initialization,
    reportSelected: reportSelected
  };
})();
