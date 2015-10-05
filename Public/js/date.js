// JavaScript Document

var  KYO = (function () {
    var  KYO = function () { },
    config = {
    	dayBgColor: ['#90E3F7', '#FFFFFF', '#FFDA44'],  //鼠标移动的颜色
        dayColor: [ '#0000FF', '#000000', '#888888'],  //日期字体颜色:1. 上月和下月的日期; 2.本月; 3.不可选时
        format: 'yyyy-MM-dd' ,   //返回日期值的格式
        outObject:  null,		//输出控件，为了按钮和文本框组合使用
        startDay: null,        //起始日期
        minDate:  '20140101',  //日期范围最小值(yyyyMMdd) 0.表示不设定
        maxDate:  0,           //日期范围最大值(yyyyMMdd) 0.表示不设定
        ranged: 1,             //是否包含日期边界值: 0.不包含; 1.包含
        showClear:  true,     //是否显示清空按钮
        today: null,
        bgDivID:  'KYO_BG_DIV' ,
        dayPanelId: 'KYO_TheCurDay',
        yearCell: 'KYO_TheCurYear',
        monthCell:  'KYO_TheCurMonth' ,
        clearButtonId: 'KYO_ClearButton' ,
        monthSelector: 'KYO_MonthSelector' ,
        yearSelector: 'KYO_YearSelector'
    },
    lang = {
        monthText: {
            zh: [ '一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            en: [ 'Jan' , 'Feb' , 'Mar' , 'Apr' , 'May' , 'Jun' , 'Jul' , 'Aug' , 'Sept', 'Oct' , 'Nov' , 
'Dec' ]
        },
        weekDay: { zh: [ '日', '一', '二', '三', '四', '五', '六'],
            en: [ 'Sun' , 'Mon' , 'Tue' , 'Wed' , 'Thu' , 'Fri' , 'Sat' ]
        },
        clearBn: { zh: '清空', en: 'Clear' },
        todayBn: { zh: '今天', en: 'Today' },
        closeBn: { zh: '关闭', en: 'Close' },
        yearCell: { zh:  '单击选择年份', en: 'Click to select the year'  },
        monthCell: { zh: '单击选择月份', en: 'Click to select the month' }
    },
    trim = function (str) {
        return str.replace(/(\s*$)|(^\s*)/g,  '');
    },
    $ = function (id, doc) {
        var  doc = doc || document;
        return doc.getElementById(id);
    },
    $$ = function (name, doc) {
        var  doc = doc || document;
        return doc.createElement(name);
    },
    browser = (function () {
        var  ua = navigator.userAgent.toLowerCase();
        return {
            VERSION: parseInt(ua.match(/(msie|firefox|webkit|opera)[\/:\s](\d+)/) ? RegExp.$2 : 
'0' ),
            IE: (ua.indexOf('msie') > - 1 && ua.indexOf('opera') == -1),
            GECKO: (ua.indexOf( 'gecko') > - 1 && ua.indexOf('khtml') == -1),
            WEBKIT: (ua.indexOf('applewebkit') > - 1),
            OPERA: (ua.indexOf( 'opera') > - 1)
        };
    })(),
    util = {
        today: function () {
             if (config.today != null) { return config.today; }
             return new  Date();
        },
        getLangText: function (text) {
             var  language = (navigator.appName == 'Netscape' ) ? navigator.language : 
navigator.browserLanguage;
             return text[(language.indexOf( 'en-' ) >=  0) ? 'en' :  'zh'];
        },
        createTable: function (doc) {
             var  table = $$( 'table', doc);
            util.setObjectStyle(table, "width:100%;margin:0;padding:0;border:0;cursor:default;");
            return table;
        },

        /* kyo Start  2014-07-12 14:54 */

        createBtnIcon: function (icon, func, id)
        {
            var span = $$('span');
            span.setAttribute('class', 'glyphicon glyphicon-' + icon);
            util.setObjectStyle(span, "cursor:pointer");
            if (id) 
                span.id = id;
            span.onclick = func;
            return span;
        },

        /* kyo End 2014-07-12 14:54 */

        createButton: function (text, func, id) {
            var  button = $$('input');
            button.setAttribute('type', 'button');
            button.setAttribute('class', 'btn btn-default btn-sm');
            button.value = text;
             if (id) 
                button.id = id;
            button.onclick = func;
            return button;
        },
        copyConfig: function () {
             var  arg = ['format', 'outObject', 'minDate', 'maxDate', 'ranged', 'showClear', 
'startDay' ];
             var  set = {};
             for  (var  i =  0; i < arg.length; i++) { set[arg[i]] = config[arg[i]]; }
            config[ 'set' ] = set; return config['set' ];
        },
        formatDate: function (date, format) {
             var  lang = {  'M+': date.getMonth() + 1, 'd+': date.getDate() };
             if (/(y+)/.test(format)) {
                format = format.replace(RegExp.$1, (date.getFullYear() +  '').substr(4 - RegExp.$1.length));
            }
             for  (var  key  in lang) {
                if (new  RegExp('('  + key + ')' ).test(format)) {
                    format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? lang[key] : ('00' + lang[key]).substr(('' + lang[key]).length));
                }
            }
             return format;
        },
        getCoords:  function (ev) {
            ev = ev || window.event;
             return { x: ev.clientX, y: ev.clientY };
        },
        getDocumentElement: function (doc) {
            doc = doc || document;
             return (doc.compatMode != "CSS1Compat" ) ? doc.body : doc.documentElement;
        },
        getScrollPos: function () {
             var  x, y;
             if (browser.IE || browser.OPERA) {
                var  el = this.getDocumentElement();
                x = el.scrollLeft; y = el.scrollTop;
            } else {
                x = window.scrollX; y = window.scrollY;
            }
             return { x: x, y: y };
        },
        getElementPos: function (el) {
             var  x =  0, y = 0;
             if (el.getBoundingClientRect) {
                var  box = el.getBoundingClientRect();
                var  el = this.getDocumentElement();
                var  pos = this.getScrollPos();
                x = box.left + pos.x - el.clientLeft;
                y = box.top + pos.y - el.clientTop;
            } else {
                x = el.offsetLeft; y = el.offsetTop;
                var  parent = el.offsetParent;
                while  (parent) {
                    x += parent.offsetLeft; y += parent.offsetTop;
                    parent = parent.offsetParent;
                }
            }
             return { x: x, y: y };
        },
        setObjectStyle:  function (obj, style) {
            obj.setAttribute('style', style);
            obj.style.cssText = style;
        },
        /* kyo Start  2014-07-12 03:34 */
        //设置对象css属性
        setObjectCss: function (obj, css, flag) {
            if (flag)
                obj.setAttribute('class', css);
            else
                obj.setAttribute('class', obj.getAttribute('class') + css);
        },
        /* kyo End 2014-07-12 03:34 */
        setDate:  function (dateObj) {
             if (dateObj == null || dateObj == undefined) { return null; }
             var  ret = null;
             var  x =  typeof (dateObj);
             try  {
                if (x == 'string') {
                    ret =  new  Date(trim(dateObj).replace(/[^ 0-9: ]+/g, '/' ));
                } else if (x == 'object') {
                    if (dateObj.getTime()) { ret = new  Date(dateObj.getTime()); }
                }
            } catch  (e) { ret = null; }
             return ret;
        },
        isLeapYear: function (year) {    //是否为闰年
             return (0 == year % 4 && ((year % 100  != 0) || (year % 400  == 0)));
        },
        getMonthCount: function (yy, mm) {
             var  count = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
             var  days = count[mm -  1];
             if (mm == 2 && util.isLeapYear(yy)) { days++; }
             return days;
        },
        getNYearMonth: function (yy, mm, n) {
             if (n > 0) {
                if (mm + n > 12) { mm = 1; yy++; }
                else { mm++; }
            } else {
                if (mm + n <=  0) { yy--; mm = 12; }
                else { mm--; }
            }
             return { YY: yy, MM: mm };
        },
        getDateNumbers:  function (date) {
             return { year: date.getFullYear(), month: date.getMonth() +  1, day: date.getDate() };
        },
        getIntegerOfDay: function (yy, mm, dd) {
             if (yy == null) { return 0; }
             if (arguments.length == 1) {
                var  x = util.getDateNumbers(yy);
                return x.year *  10000  + x.month * 100  + x.day;
            } else {
                return yy * 10000  + mm * 100  + dd;
            }
        },
        checkDateRange:  function (yyMMdd) {
             var  set = config['set' ];
             if (set.maxDate ==  0 && set.minDate ==  0) { return true; }
             if (set.minDate ==  0) {
                return set.ranged == 1 ? (yyMMdd <= set.maxDate) : (yyMMdd < set.maxDate);
            } else if (set.maxDate ==  0) {
                return set.ranged == 1 ? (yyMMdd >= set.minDate) : (yyMMdd > set.minDate);
            }
             if (set.ranged == 1) {
                return yyMMdd >= set.minDate && yyMMdd <= set.maxDate;
            } else {
                return yyMMdd > set.minDate && yyMMdd < set.maxDate;
            }
        },
        getEventSrcObject: function () {
             var  theEvent = window.event;
             if (theEvent == undefined) {
                var  caller = arguments.callee.caller;
                while  (caller.caller !=  null) { caller = caller.caller; }
                theEvent = caller.arguments[0];
            }
             return theEvent.srcElement ? theEvent.srcElement : theEvent.target;
        },
        outObjectValue:  function (arg) {
             var  obj = config['set' ].outObject;
             if (arguments.length < 1) {
                return trim(obj.tagName.toLowerCase() == 'input' ? obj.value : obj.innerHTML);
            }
             var  str = typeof (arg) ==  'object' ? util.formatDate(arg, config[ 'set' ].format) :  '';
             if (obj.tagName.toLowerCase() ==  'input') {
                obj.value = str;
            } else {
                obj.innerHTML = str;
            }
        }
    },
    events = {
        addEvent: function (el, event, listener) {
             if (el.addEventListener) {
                el.addEventListener(event, listener, false );
            } else if (el.attachEvent) {
                el.attachEvent( 'on' + event, listener);
            }
        },
        removeEvent: function (el, event, listener) {
             if (el.removeEventListener) {
                el.removeEventListener(event, listener, false );
            } else if (el.detachEvent) {
                el.detachEvent( 'on' + event, listener);
            }
        },
        show: function (top, left) {
             var  outv = '';
             var  readyDay = util.today();
             if (config['set' ].startDay == null) {
                outv = util.outObjectValue();
                if (outv !=  '') {
                    outv = outv.replace(/[^0-9:]/gi, '/' );
                    if (( new  Date(outv)).toString().toLowerCase() != 'invalid date') {
                        readyDay = new  Date(outv);
                    }
                }
            } else {
                readyDay = config['set' ].startDay;
            }
             /* kyo start */
             // var readyDay = new Date();
             /* kyo end */
             
             var  div = $(config.bgDivID);
             if (div == null || div == undefined) {
                dialog();
                div = $(config.bgDivID);
            }
             var  ymd = util.getDateNumbers(readyDay);
            events.createDay(ymd.year, ymd.month, ymd.day);
            div.style.top = top + 'px';
            div.style.left = left + 'px';
            div.style.display = '';
            events.addEvent(document,  'keydown', events.keyDown);
            events.addEvent(document,  'mousedown', events.mouseDown);
            $(config.clearButtonId).style.display = config[ 'set' ].showClear ? '' :  'none';
        },
        setHeaderYM: function (yy, mm) {
            $(config.yearCell).innerHTML = yy;
            $(config.monthCell).innerHTML = util.getLangText(lang.monthText)[mm -  1];
            $(config.monthCell).axis = mm;
        },
        createDay:  function (yy, mm, dd) {
             this.dayRangeEv = function (obj, nday, x) {
                var  isRange = util.checkDateRange(nday);
                obj.axis = x; obj.className = '';
                obj.style.backgroundColor = config.dayBgColor[1];
                obj.style.color = isRange === true ? config.dayColor[x === 0 ?  1 :  0] : 
config.dayColor[2];
                obj.style.cursor = isRange ===  true ?  'pointer' :  'default';
                obj.onmousemove = isRange === true ? events.dayOnMouseMove : events.emptyFunc;
                obj.onmouseout = isRange ===  true ? events.dayOnMouseOut : events.emptyFunc;
                obj.onclick = isRange === true ? events.dayOnClick : events.emptyFunc;
            };
             var  dd = dd ||  0;
            events.setHeaderYM(yy, mm);
             var  date = new  Date(yy, mm - 1, 1);
             var  firstCount = date.getDay();
            firstCount = firstCount <  2 ? firstCount + 7 : firstCount;
             //上个月的尾数
             var  pre = util.getNYearMonth(yy, mm, -1);
             var  preMonthDay = util.getMonthCount(pre.YY, pre.MM);
             var  obj = null;
             for  (var  i = firstCount - 1; i >= 0; i--) {
                obj = $(config.dayPanelId + i);
                obj.innerHTML = preMonthDay - firstCount + i + 1;
                this.dayRangeEv(obj, util.getIntegerOfDay(pre.YY, pre.MM, preMonthDay - 
firstCount + i +  1), -1);
            }
             //本月
             var  monthDays = util.getMonthCount(yy, mm);
             for  (var  i =  0; i < monthDays; i++) {
                obj = $(config.dayPanelId + (firstCount + i));
                obj.innerHTML = i + 1;
                this.dayRangeEv(obj, util.getIntegerOfDay(yy, mm, i + 1),  0);
                if ((dd != i + 1) || obj.style.cursor == 'default') {
                    obj.style.backgroundColor = config.dayBgColor[ 1];
                    obj.className = '';
                } else {
                    obj.style.backgroundColor = config.dayBgColor[ 0];
                    obj.className = 'KYO_CHOOSEDAY';
                }
            }
             //下个月
             var  xx = firstCount + monthDays;
             var  next = util.getNYearMonth(yy, mm, 1);
             for  (var  i =  0; i < 42 - firstCount - monthDays; i++) {
                obj = $(config.dayPanelId + (xx + i));
                obj.innerHTML = i + 1;
                this.dayRangeEv(obj, util.getIntegerOfDay(next.YY, next.MM, i + 1),  1);
            }
        },
        dayOnMouseMove:  function () {
             this.style.backgroundColor = config.dayBgColor[0];
        },
        dayOnMouseOut: function () {
             if (this.className != 'KYO_CHOOSEDAY') {
                this.style.backgroundColor = config.dayBgColor[1];
            }
        },
        dayOnClick: function () {
            var  year = parseInt($(config.yearCell).innerHTML.match(/\d+/g)),
            month = parseInt($(config.monthCell).axis),
            day = parseInt(this.innerHTML);
             if (this.axis != '0' ) {
                var  n = day > 20 ? -1 :  1;
                var  ready = util.getNYearMonth(year, month, n);
                year = ready.YY; month = ready.MM;
            }
            var  date = new  Date(year + '/'  + month + '/'  + day);
            util.outObjectValue(date);
            events.hideLayout();
        },
        emptyFunc:  function () { },
        turnMonth:  function (n) {
             var  year = parseInt($(config.yearCell).innerHTML.match(/\d+/g)),
            month = parseInt($(config.monthCell).axis);
             if (n != 0) {
                var  xday = util.getNYearMonth(year, month, n);
                year = xday.YY; month = xday.MM;
            }
            events.createDay(year, month);
        },
        showSelector: function (flag) {
             var  flag = flag || 1;
            events.hideSelector(flag ==  1 ?  2 :  1);
             var  div = flag == 1 ? $(config.yearSelector) : $(config.monthSelector);
             var  bool = (div == undefined || div == null);
             if (bool) {
                div = $$('div' );
            } else {
                if (div.style.display != 'none') { events.hideSelector(flag);  return; }
                var  divChildren = div.childNodes;
                for  (var  i = divChildren.length -  1; i >= 0; i--) {
                    div.removeChild(divChildren[i]);
                }
            }
             var  coord = {
                x: parseInt($(config.bgDivID).style.left.match(/\d+/g)) + (flag == 1 ?  32 :  85),
                y: parseInt($(config.bgDivID).style.top.match(/\d+/g)) +  25
            };
            div.id = flag == 1 ? config.yearSelector : config.monthSelector;
             var  _style =  'position:absolute;z-index:20010;background-color:#FFFFFF;top:' +
                coord.y + 'px;left:' + coord.x + 'px; padding:10px;';
            util.setObjectStyle(div, _style);
            util.setObjectCss(div, "pop_win", true);
             var  table = util.createTable();
             if (flag ==  1) {
                var  start = parseInt($(config.yearCell).innerHTML.match(/\d+/g)) - 5;
                events.yearSelector(table, start);
            } else {
                events.monthSelector(table);
            }
            div.appendChild(table);
             if (bool) {
                document.body.appendChild(div);
            } else {
                div.style.display = '';
            }
        },
        yearSelector: function (table, start, rep) {
             var  objid = [ 'KYO_YEATSELECTOR_PRET', 'KYO_YEATSELECTOR_NEXT', 'KYO_YEARSELECTOR'];
             var  rep = rep || 0;
             if (rep == 1) {
                for  (var  i =  0; i < 10; i++) {
                    $(objid[2] + i).innerHTML = start + i;
                }
                $(objid[ 0]).onclick = function () { events.yearSelector(table, start - 10, 1); };
                $(objid[ 1]).onclick = function () { events.yearSelector(table, start + 10, 1); };
                return;
            }
             var  row, cell;
             for  (var  i =  0; i < 5; i++) {
                row = table.insertRow(i);
                for  (var  j =  0; j < 2; j++) {
                    cell = row.insertCell(j);
                    cell.id = objid[2] + (j ==  0 ? i : i + 5);
                    cell.innerHTML = start + i + j * 5;
                    var  cellStyle = 'width:60px;height:30px;border-radius:10px;text-align:center;cursor:pointer;';
                    util.setObjectStyle(cell, cellStyle);
                    cell.onmousemove = events.dayOnMouseMove;
                    cell.onmouseout = events.dayOnMouseOut;
                    cell.onclick = function () {
                        $(config.yearCell).innerHTML = this.innerHTML;
                        events.turnMonth(0);
                        events.hideSelector(1);
                    };
                }
            }
            row = table.insertRow(5);
            ctrl_cell = row.insertCell(0);
            ctrl_cell.colSpan = 2;
            var ctrl_table = util.createTable();
            util.setObjectStyle(ctrl_table, "width:100%;text-align:center");
            row = ctrl_table.insertRow(0);
            cell = row.insertCell(0);
             var  button = util.createBtnIcon('arrow-left', function () {
                var  s1 = start - 10; events.yearSelector(table, s1,  1);
            }, objid[0]);
            cell.appendChild(button);
            cell = row.insertCell(1);
            button = util.createBtnIcon('remove', function () { events.hideSelector( 1); });
            cell.appendChild(button);
            cell = row.insertCell(2);
            button = util.createBtnIcon('arrow-right', function () {
                var  s2 = start + 10; events.yearSelector(table, s2,  1);
            }, objid[1]);
            cell.appendChild(button);
            ctrl_cell.appendChild(ctrl_table);
        },
        monthSelector: function (table) {
             var  array = util.getLangText(lang.monthText);
             for  (var  i =  0; i < 6; i++) {
                var  row = table.insertRow(i);
                for  (var  j =  0; j < 2; j++) {
                    var  cell = row.insertCell(j);
                    cell.innerHTML = array[i + j * 6];
                    cell.axis = i + 1 + j * 6;
                    var  cellStyle = 'width:60px;height:30px;border-radius:10px;text-align:center;cursor:pointer;';
                    util.setObjectStyle(cell, cellStyle);
                    cell.onmousemove = events.dayOnMouseMove;
                    cell.onmouseout = events.dayOnMouseOut;
                    cell.onclick = function () {
                        $(config.monthCell).axis = this.axis;
                        events.turnMonth(0);
                        events.hideSelector(2);
                    };
                }
            }
        },
        hideSelector: function (flag) {
             if (flag ==  2) {
                if ($(config.monthSelector)) { $(config.monthSelector).style.display = 'none'; }
            } else {
                if ($(config.yearSelector)) { $(config.yearSelector).style.display = 'none'; }
            }
        },
        hideLayout: function () {
             var  div = $(config.bgDivID);
            events.hideSelector(1);
            events.hideSelector(2);
            div.style.display = 'none';
            events.removeEvent(document, 'keydown', events.keyDown);
            events.removeEvent(document, 'mousedown', events.mouseDown);
        },
        keyDown:  function (ev) {
            ev = ev || window.event;
             if (ev.keyCode == 27) { events.hideLayout(); }
        },
        mouseDown:  function (ev) {
             var  div = $(config.bgDivID);
             if (div.style.display == 'none') { return; }
             var  ymFlag =  0;
             if ($(config.yearSelectCtrl)) { ymFlag = 1; }
             if ($(config.monthSelectCtrl)) { ymFlag =  1; }
             var  minLeft, minTop, maxLeft, maxTop;
             var  pos = util.getElementPos(div);
            minLeft = pos.x; minTop = pos.y;
            maxLeft = minLeft + 230;
            maxTop = minTop + 290;
             var  scrol = util.getScrollPos();
             var  mouse = util.getCoords(ev);
             var  x = scrol.x + mouse.x;
             var  y = scrol.y + mouse.y;
             if (ymFlag ==  1) {
                if (x < minLeft || x > maxLeft) { events.hideLayout(); }
                return;
            }
             if (x < minLeft || x > maxLeft || y < minTop || y > maxTop) {
                events.hideLayout();
            }
        }
    },
    dialog = function () {
        this.getHeaderPanel = function () {
             var  table = util.createTable();
             var  row = table.insertRow(0);
             var  cell = row.insertCell(0);
            // cell.style.height = '23px';
             var  bn = util.createBtnIcon('chevron-left' , function () { events.turnMonth(- 1); }, 
'bn_preMonth');
            cell.appendChild(bn);
            cell = row.insertCell(1);
            util.setObjectStyle(cell, "cursor:pointer;border-radius:10px");
            cell.id = config.yearCell;
            cell.title = util.getLangText(lang.yearCell);
            cell.onmousemove =  function () { this.style.backgroundColor = 
config.dayBgColor[2]; };
            cell.onmouseout = function () { this.style.backgroundColor = config.dayBgColor[1]; };
            cell.onclick = function () { events.showSelector( 1); };
            cell = row.insertCell(2);
            util.setObjectStyle(cell, "cursor:pointer;border-radius:10px");
            cell.id = config.monthCell;
            cell.title = util.getLangText(lang.monthCell);
            cell.onmousemove =  function () { this.style.backgroundColor = 
config.dayBgColor[2]; };
            cell.onmouseout = function () { this.style.backgroundColor = config.dayBgColor[1]; };
            cell.onclick = function () { events.showSelector( 2); };
            cell = row.insertCell(3);
            bn = util.createBtnIcon('chevron-right' , function () { events.turnMonth(1); });
            cell.appendChild(bn);
             return table;
        };
        this.getWeekDayPanel =  function () {
             var  table = util.createTable();
            style = 'width:30px;height:30px;border-radius:10px;cursor:pointer;';
             var  panelId = 0; var  row = null;
             for  (var  m =  0; m < 6; m++) {
                row = table.insertRow(m);
                for  (var  n =  0; n < 7; n++) {
                    cell = row.insertCell(n);
                    cell.id = config.dayPanelId + panelId;
                    util.setObjectStyle(cell, style);
                    panelId++;
                }
            }
            row = table.insertRow(0);
             var  week = util.getLangText(lang.weekDay);
             for  (var  i =  0; i < 7; i++) {
                var  cell = row.insertCell(i);
                util.setObjectStyle(cell, "font-weight:bold;width:30px;height:30px");
                cell.innerHTML = week[i];
            }
             return table;
        };
        this.getBottomPanel = function () {  //创建下面一行操作按钮
            var  table = util.createTable();
            var  row = table.insertRow(0);
			var cell = row.insertCell(0);
            util.setObjectStyle(cell, "text-align:left");
            var bn = util.createButton(util.getLangText(lang.todayBn), function () {
                var  xnow = util.getDateNumbers(util.today());
                //events.createDay(xnow.year, xnow.month, xnow.day);  //选中当前日期
				var  date = new  Date(xnow.year + '/'  + xnow.month + '/'  + xnow.day);
				util.outObjectValue(date);
				events.hideLayout();
            });
            cell.appendChild(bn);
			
            cell = row.insertCell(1);
            util.setObjectStyle(cell, "text-align:center");
            bn = util.createButton(util.getLangText(lang.clearBn), function () 
{ util.outObjectValue(''); }, config.clearButtonId);
            cell.appendChild(bn);
            cell = row.insertCell(2);
            util.setObjectStyle(cell, "text-align:right");
            bn = util.createButton(util.getLangText(lang.closeBn), events.hideLayout);
            cell.appendChild(bn);
            return table;
        };

        var  container = util.createTable();
        util.setObjectStyle(container, "border:0;margin:0;padding:0;text-align:center;width:100%");
        var  row = container.insertRow(0);
        var  cell = row.insertCell(0);
        cell.appendChild(this.getHeaderPanel());
        row = container.insertRow(1);
        cell = row.insertCell(0);
        cell.appendChild(this.getWeekDayPanel());
        row = container.insertRow(2);
        cell = row.insertCell(0);
        cell.appendChild(this.getBottomPanel());
        var  div = $$( 'div' );
        div.id = config.bgDivID;
        _style =  'position:absolute;width:230px;height:290px;z-index:20000;display:none;background-color:#FFFFFF;padding:10px';
        util.setObjectStyle(div, _style);
        /* kyo Start  2014-07-12 03:23 */
        div.setAttribute('class', "pop_win");
        /* kyo End 2014-07-12 03:23 */

        div.appendChild(container);
        document.body.appendChild(div);
    };
    KYO.setday =  function (args) {
        var  obj = util.getEventSrcObject();
        var  x = util.copyConfig();
        x.outObject = obj;
        
        this.checkObj =  function (str) { return $(str) != null && $(str) != undefined; };
        if (args) {
             if (typeof (args) == 'string' && this.checkObj(args)) {
                x.outObject = $(args);
            }
             else if (typeof (args.outObject) == 'string' && this.checkObj(args.outObject)) {
                x.outObject = $(args.outObject);
            } else if (typeof (args.outObject) == 'object') { x.outObject = args.outObject; }
            x.showClear = (args.showClear || config.showClear) === false  ?  false  :  true;
            x.format = args.format || config.format;
             if (args.today) { KYO.setToday(args.today); }
             if (args.minDate != undefined || args.maxDate != undefined) {
                x.minDate = util.getIntegerOfDay(util.setDate(args.minDate));
                x.maxDate = util.getIntegerOfDay(util.setDate(args.maxDate));
                x.ranged = (args.ranged || config.ranged) === false  ?  0 :  1;
            }
             if (args.readOnly === true) { x.outObject.readOnly = true; }
             if (args.startDay) { x.startDay = util.setDate(args.startDay); }
        }
        var  pos = { top: obj.offsetTop, left: obj.offsetLeft, height: obj.clientHeight };
        while  (obj = obj.offsetParent) {
            pos.top += obj.offsetTop;
            pos.left += obj.offsetLeft;
        }
        var  objTop = (typeof (obj) ==  'image') ? pos.top + pos.height : pos.top + pos.height + 5;
        events.show(objTop, pos.left);
    };
    KYO.setToday =  function (dateObj) {
        config.today = util.setDate(dateObj);
    };
    KYO.setDateRange = function (minDate, maxDate, ranged) {
        config.minDate = util.getIntegerOfDay(util.setDate(minDate));
        config.maxDate = util.getIntegerOfDay(util.setDate(maxDate));
        config.ranged = ranged === false  ?  0 :  1;
    };
    KYO.setDateFormat =  function (format) {
        config.format = format || config.format;
    };
    KYO.setStartDay = function (date) {
        config.startDay = util.setDate(date);
    };
    return KYO;
})();
