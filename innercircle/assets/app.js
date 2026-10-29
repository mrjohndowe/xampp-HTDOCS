"use strict";
(() => {
  var gg = Object.create;
  var tr = Object.defineProperty;
  var xg = Object.getOwnPropertyDescriptor;
  var Cg = Object.getOwnPropertyNames;
  var yg = Object.getPrototypeOf,
    Sg = Object.prototype.hasOwnProperty;
  var Da = (e, a) => () => {
    try {
      return (a || e((a = { exports: {} }).exports, a), a.exports);
    } catch (t) {
      throw ((a = 0), t);
    }
  };
  var vg = (e, a, t, l) => {
    if ((a && typeof a == "object") || typeof a == "function")
      for (let u of Cg(a))
        !Sg.call(e, u) &&
          u !== t &&
          tr(e, u, {
            get: () => a[u],
            enumerable: !(l = xg(a, u)) || l.enumerable,
          });
    return e;
  };
  var ce = (e, a, t) => (
    (t = e != null ? gg(yg(e)) : {}),
    vg(
      a || !e || !e.__esModule
        ? tr(t, "default", { value: e, enumerable: !0 })
        : t,
      e,
    )
  );
  var cr = Da((se) => {
    "use strict";
    function tf(e, a) {
      var t = e.length;
      e.push(a);
      e: for (; 0 < t; ) {
        var l = (t - 1) >>> 1,
          u = e[l];
        if (0 < dd(u, a)) ((e[l] = a), (e[t] = u), (t = l));
        else break e;
      }
    }
    function Ta(e) {
      return e.length === 0 ? null : e[0];
    }
    function fd(e) {
      if (e.length === 0) return null;
      var a = e[0],
        t = e.pop();
      if (t !== a) {
        e[0] = t;
        e: for (var l = 0, u = e.length, o = u >>> 1; l < o; ) {
          var d = 2 * (l + 1) - 1,
            f = e[d],
            n = d + 1,
            p = e[n];
          if (0 > dd(f, t))
            n < u && 0 > dd(p, f)
              ? ((e[l] = p), (e[n] = t), (l = n))
              : ((e[l] = f), (e[d] = t), (l = d));
          else if (n < u && 0 > dd(p, t)) ((e[l] = p), (e[n] = t), (l = n));
          else break e;
        }
      }
      return a;
    }
    function dd(e, a) {
      var t = e.sortIndex - a.sortIndex;
      return t !== 0 ? t : e.id - a.id;
    }
    se.unstable_now = void 0;
    typeof performance == "object" && typeof performance.now == "function"
      ? ((lr = performance),
        (se.unstable_now = function () {
          return lr.now();
        }))
      : (($s = Date),
        (ur = $s.now()),
        (se.unstable_now = function () {
          return $s.now() - ur;
        }));
    var lr,
      $s,
      ur,
      Ga = [],
      mt = [],
      Ig = 1,
      fa = null,
      ze = 3,
      lf = !1,
      Ru = !1,
      Ou = !1,
      uf = !1,
      sr = typeof setTimeout == "function" ? setTimeout : null,
      fr = typeof clearTimeout == "function" ? clearTimeout : null,
      or = typeof setImmediate < "u" ? setImmediate : null;
    function sd(e) {
      for (var a = Ta(mt); a !== null; ) {
        if (a.callback === null) fd(mt);
        else if (a.startTime <= e)
          (fd(mt), (a.sortIndex = a.expirationTime), tf(Ga, a));
        else break;
        a = Ta(mt);
      }
    }
    function of(e) {
      if (((Ou = !1), sd(e), !Ru))
        if (Ta(Ga) !== null) ((Ru = !0), Rl || ((Rl = !0), zl()));
        else {
          var a = Ta(mt);
          a !== null && df(of, a.startTime - e);
        }
    }
    var Rl = !1,
      Eu = -1,
      nr = 5,
      ir = -1;
    function rr() {
      return uf ? !0 : !(se.unstable_now() - ir < nr);
    }
    function ef() {
      if (((uf = !1), Rl)) {
        var e = se.unstable_now();
        ir = e;
        var a = !0;
        try {
          e: {
            ((Ru = !1), Ou && ((Ou = !1), fr(Eu), (Eu = -1)), (lf = !0));
            var t = ze;
            try {
              a: {
                for (
                  sd(e), fa = Ta(Ga);
                  fa !== null && !(fa.expirationTime > e && rr());
                ) {
                  var l = fa.callback;
                  if (typeof l == "function") {
                    ((fa.callback = null), (ze = fa.priorityLevel));
                    var u = l(fa.expirationTime <= e);
                    if (((e = se.unstable_now()), typeof u == "function")) {
                      ((fa.callback = u), sd(e), (a = !0));
                      break a;
                    }
                    (fa === Ta(Ga) && fd(Ga), sd(e));
                  } else fd(Ga);
                  fa = Ta(Ga);
                }
                if (fa !== null) a = !0;
                else {
                  var o = Ta(mt);
                  (o !== null && df(of, o.startTime - e), (a = !1));
                }
              }
              break e;
            } finally {
              ((fa = null), (ze = t), (lf = !1));
            }
            a = void 0;
          }
        } finally {
          a ? zl() : (Rl = !1);
        }
      }
    }
    var zl;
    typeof or == "function"
      ? (zl = function () {
          or(ef);
        })
      : typeof MessageChannel < "u"
        ? ((af = new MessageChannel()),
          (dr = af.port2),
          (af.port1.onmessage = ef),
          (zl = function () {
            dr.postMessage(null);
          }))
        : (zl = function () {
            sr(ef, 0);
          });
    var af, dr;
    function df(e, a) {
      Eu = sr(function () {
        e(se.unstable_now());
      }, a);
    }
    se.unstable_IdlePriority = 5;
    se.unstable_ImmediatePriority = 1;
    se.unstable_LowPriority = 4;
    se.unstable_NormalPriority = 3;
    se.unstable_Profiling = null;
    se.unstable_UserBlockingPriority = 2;
    se.unstable_cancelCallback = function (e) {
      e.callback = null;
    };
    se.unstable_forceFrameRate = function (e) {
      0 > e || 125 < e
        ? console.error(
            "forceFrameRate takes a positive int between 0 and 125, forcing frame rates higher than 125 fps is not supported",
          )
        : (nr = 0 < e ? Math.floor(1e3 / e) : 5);
    };
    se.unstable_getCurrentPriorityLevel = function () {
      return ze;
    };
    se.unstable_next = function (e) {
      switch (ze) {
        case 1:
        case 2:
        case 3:
          var a = 3;
          break;
        default:
          a = ze;
      }
      var t = ze;
      ze = a;
      try {
        return e();
      } finally {
        ze = t;
      }
    };
    se.unstable_requestPaint = function () {
      uf = !0;
    };
    se.unstable_runWithPriority = function (e, a) {
      switch (e) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
          break;
        default:
          e = 3;
      }
      var t = ze;
      ze = e;
      try {
        return a();
      } finally {
        ze = t;
      }
    };
    se.unstable_scheduleCallback = function (e, a, t) {
      var l = se.unstable_now();
      switch (
        (typeof t == "object" && t !== null
          ? ((t = t.delay), (t = typeof t == "number" && 0 < t ? l + t : l))
          : (t = l),
        e)
      ) {
        case 1:
          var u = -1;
          break;
        case 2:
          u = 250;
          break;
        case 5:
          u = 1073741823;
          break;
        case 4:
          u = 1e4;
          break;
        default:
          u = 5e3;
      }
      return (
        (u = t + u),
        (e = {
          id: Ig++,
          callback: a,
          priorityLevel: e,
          startTime: t,
          expirationTime: u,
          sortIndex: -1,
        }),
        t > l
          ? ((e.sortIndex = t),
            tf(mt, e),
            Ta(Ga) === null &&
              e === Ta(mt) &&
              (Ou ? (fr(Eu), (Eu = -1)) : (Ou = !0), df(of, t - l)))
          : ((e.sortIndex = u),
            tf(Ga, e),
            Ru || lf || ((Ru = !0), Rl || ((Rl = !0), zl()))),
        e
      );
    };
    se.unstable_shouldYield = rr;
    se.unstable_wrapCallback = function (e) {
      var a = ze;
      return function () {
        var t = ze;
        ze = a;
        try {
          return e.apply(this, arguments);
        } finally {
          ze = t;
        }
      };
    };
  });
  var pr = Da((Iy, mr) => {
    "use strict";
    mr.exports = cr();
  });
  var Ar = Da((O) => {
    "use strict";
    var nf = Symbol.for("react.transitional.element"),
      bg = Symbol.for("react.portal"),
      Ag = Symbol.for("react.fragment"),
      kg = Symbol.for("react.strict_mode"),
      Mg = Symbol.for("react.profiler"),
      wg = Symbol.for("react.consumer"),
      Dg = Symbol.for("react.context"),
      Tg = Symbol.for("react.forward_ref"),
      Bg = Symbol.for("react.suspense"),
      zg = Symbol.for("react.memo"),
      Cr = Symbol.for("react.lazy"),
      Rg = Symbol.for("react.activity"),
      hr = Symbol.iterator;
    function Og(e) {
      return e === null || typeof e != "object"
        ? null
        : ((e = (hr && e[hr]) || e["@@iterator"]),
          typeof e == "function" ? e : null);
    }
    var yr = {
        isMounted: function () {
          return !1;
        },
        enqueueForceUpdate: function () {},
        enqueueReplaceState: function () {},
        enqueueSetState: function () {},
      },
      Sr = Object.assign,
      vr = {};
    function El(e, a, t) {
      ((this.props = e),
        (this.context = a),
        (this.refs = vr),
        (this.updater = t || yr));
    }
    El.prototype.isReactComponent = {};
    El.prototype.setState = function (e, a) {
      if (typeof e != "object" && typeof e != "function" && e != null)
        throw Error(
          "takes an object of state variables to update or a function which returns an object of state variables.",
        );
      this.updater.enqueueSetState(this, e, a, "setState");
    };
    El.prototype.forceUpdate = function (e) {
      this.updater.enqueueForceUpdate(this, e, "forceUpdate");
    };
    function Ir() {}
    Ir.prototype = El.prototype;
    function rf(e, a, t) {
      ((this.props = e),
        (this.context = a),
        (this.refs = vr),
        (this.updater = t || yr));
    }
    var cf = (rf.prototype = new Ir());
    cf.constructor = rf;
    Sr(cf, El.prototype);
    cf.isPureReactComponent = !0;
    var Lr = Array.isArray;
    function ff() {}
    var le = { H: null, A: null, T: null, S: null },
      br = Object.prototype.hasOwnProperty;
    function mf(e, a, t) {
      var l = t.ref;
      return {
        $$typeof: nf,
        type: e,
        key: a,
        ref: l !== void 0 ? l : null,
        props: t,
      };
    }
    function Eg(e, a) {
      return mf(e.type, a, e.props);
    }
    function pf(e) {
      return typeof e == "object" && e !== null && e.$$typeof === nf;
    }
    function qg(e) {
      var a = { "=": "=0", ":": "=2" };
      return (
        "$" +
        e.replace(/[=:]/g, function (t) {
          return a[t];
        })
      );
    }
    var gr = /\/+/g;
    function sf(e, a) {
      return typeof e == "object" && e !== null && e.key != null
        ? qg("" + e.key)
        : a.toString(36);
    }
    function Ng(e) {
      switch (e.status) {
        case "fulfilled":
          return e.value;
        case "rejected":
          throw e.reason;
        default:
          switch (
            (typeof e.status == "string"
              ? e.then(ff, ff)
              : ((e.status = "pending"),
                e.then(
                  function (a) {
                    e.status === "pending" &&
                      ((e.status = "fulfilled"), (e.value = a));
                  },
                  function (a) {
                    e.status === "pending" &&
                      ((e.status = "rejected"), (e.reason = a));
                  },
                )),
            e.status)
          ) {
            case "fulfilled":
              return e.value;
            case "rejected":
              throw e.reason;
          }
      }
      throw e;
    }
    function Ol(e, a, t, l, u) {
      var o = typeof e;
      (o === "undefined" || o === "boolean") && (e = null);
      var d = !1;
      if (e === null) d = !0;
      else
        switch (o) {
          case "bigint":
          case "string":
          case "number":
            d = !0;
            break;
          case "object":
            switch (e.$$typeof) {
              case nf:
              case bg:
                d = !0;
                break;
              case Cr:
                return ((d = e._init), Ol(d(e._payload), a, t, l, u));
            }
        }
      if (d)
        return (
          (u = u(e)),
          (d = l === "" ? "." + sf(e, 0) : l),
          Lr(u)
            ? ((t = ""),
              d != null && (t = d.replace(gr, "$&/") + "/"),
              Ol(u, a, t, "", function (p) {
                return p;
              }))
            : u != null &&
              (pf(u) &&
                (u = Eg(
                  u,
                  t +
                    (u.key == null || (e && e.key === u.key)
                      ? ""
                      : ("" + u.key).replace(gr, "$&/") + "/") +
                    d,
                )),
              a.push(u)),
          1
        );
      d = 0;
      var f = l === "" ? "." : l + ":";
      if (Lr(e))
        for (var n = 0; n < e.length; n++)
          ((l = e[n]), (o = f + sf(l, n)), (d += Ol(l, a, t, o, u)));
      else if (((n = Og(e)), typeof n == "function"))
        for (e = n.call(e), n = 0; !(l = e.next()).done; )
          ((l = l.value), (o = f + sf(l, n++)), (d += Ol(l, a, t, o, u)));
      else if (o === "object") {
        if (typeof e.then == "function") return Ol(Ng(e), a, t, l, u);
        throw (
          (a = String(e)),
          Error(
            "Objects are not valid as a React child (found: " +
              (a === "[object Object]"
                ? "object with keys {" + Object.keys(e).join(", ") + "}"
                : a) +
              "). If you meant to render a collection of children, use an array instead.",
          )
        );
      }
      return d;
    }
    function nd(e, a, t) {
      if (e == null) return e;
      var l = [],
        u = 0;
      return (
        Ol(e, l, "", "", function (o) {
          return a.call(t, o, u++);
        }),
        l
      );
    }
    function Ug(e) {
      if (e._status === -1) {
        var a = e._result;
        ((a = a()),
          a.then(
            function (t) {
              (e._status === 0 || e._status === -1) &&
                ((e._status = 1), (e._result = t));
            },
            function (t) {
              (e._status === 0 || e._status === -1) &&
                ((e._status = 2), (e._result = t));
            },
          ),
          e._status === -1 && ((e._status = 0), (e._result = a)));
      }
      if (e._status === 1) return e._result.default;
      throw e._result;
    }
    var xr =
        typeof reportError == "function"
          ? reportError
          : function (e) {
              if (
                typeof window == "object" &&
                typeof window.ErrorEvent == "function"
              ) {
                var a = new window.ErrorEvent("error", {
                  bubbles: !0,
                  cancelable: !0,
                  message:
                    typeof e == "object" &&
                    e !== null &&
                    typeof e.message == "string"
                      ? String(e.message)
                      : String(e),
                  error: e,
                });
                if (!window.dispatchEvent(a)) return;
              } else if (
                typeof process == "object" &&
                typeof process.emit == "function"
              ) {
                process.emit("uncaughtException", e);
                return;
              }
              console.error(e);
            },
      Hg = {
        map: nd,
        forEach: function (e, a, t) {
          nd(
            e,
            function () {
              a.apply(this, arguments);
            },
            t,
          );
        },
        count: function (e) {
          var a = 0;
          return (
            nd(e, function () {
              a++;
            }),
            a
          );
        },
        toArray: function (e) {
          return (
            nd(e, function (a) {
              return a;
            }) || []
          );
        },
        only: function (e) {
          if (!pf(e))
            throw Error(
              "React.Children.only expected to receive a single React element child.",
            );
          return e;
        },
      };
    O.Activity = Rg;
    O.Children = Hg;
    O.Component = El;
    O.Fragment = Ag;
    O.Profiler = Mg;
    O.PureComponent = rf;
    O.StrictMode = kg;
    O.Suspense = Bg;
    O.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE = le;
    O.__COMPILER_RUNTIME = {
      __proto__: null,
      c: function (e) {
        return le.H.useMemoCache(e);
      },
    };
    O.cache = function (e) {
      return function () {
        return e.apply(null, arguments);
      };
    };
    O.cacheSignal = function () {
      return null;
    };
    O.cloneElement = function (e, a, t) {
      if (e == null)
        throw Error(
          "The argument must be a React element, but you passed " + e + ".",
        );
      var l = Sr({}, e.props),
        u = e.key;
      if (a != null)
        for (o in (a.key !== void 0 && (u = "" + a.key), a))
          !br.call(a, o) ||
            o === "key" ||
            o === "__self" ||
            o === "__source" ||
            (o === "ref" && a.ref === void 0) ||
            (l[o] = a[o]);
      var o = arguments.length - 2;
      if (o === 1) l.children = t;
      else if (1 < o) {
        for (var d = Array(o), f = 0; f < o; f++) d[f] = arguments[f + 2];
        l.children = d;
      }
      return mf(e.type, u, l);
    };
    O.createContext = function (e) {
      return (
        (e = {
          $$typeof: Dg,
          _currentValue: e,
          _currentValue2: e,
          _threadCount: 0,
          Provider: null,
          Consumer: null,
        }),
        (e.Provider = e),
        (e.Consumer = { $$typeof: wg, _context: e }),
        e
      );
    };
    O.createElement = function (e, a, t) {
      var l,
        u = {},
        o = null;
      if (a != null)
        for (l in (a.key !== void 0 && (o = "" + a.key), a))
          br.call(a, l) &&
            l !== "key" &&
            l !== "__self" &&
            l !== "__source" &&
            (u[l] = a[l]);
      var d = arguments.length - 2;
      if (d === 1) u.children = t;
      else if (1 < d) {
        for (var f = Array(d), n = 0; n < d; n++) f[n] = arguments[n + 2];
        u.children = f;
      }
      if (e && e.defaultProps)
        for (l in ((d = e.defaultProps), d)) u[l] === void 0 && (u[l] = d[l]);
      return mf(e, o, u);
    };
    O.createRef = function () {
      return { current: null };
    };
    O.forwardRef = function (e) {
      return { $$typeof: Tg, render: e };
    };
    O.isValidElement = pf;
    O.lazy = function (e) {
      return { $$typeof: Cr, _payload: { _status: -1, _result: e }, _init: Ug };
    };
    O.memo = function (e, a) {
      return { $$typeof: zg, type: e, compare: a === void 0 ? null : a };
    };
    O.startTransition = function (e) {
      var a = le.T,
        t = {};
      le.T = t;
      try {
        var l = e(),
          u = le.S;
        (u !== null && u(t, l),
          typeof l == "object" &&
            l !== null &&
            typeof l.then == "function" &&
            l.then(ff, xr));
      } catch (o) {
        xr(o);
      } finally {
        (a !== null && t.types !== null && (a.types = t.types), (le.T = a));
      }
    };
    O.unstable_useCacheRefresh = function () {
      return le.H.useCacheRefresh();
    };
    O.use = function (e) {
      return le.H.use(e);
    };
    O.useActionState = function (e, a, t) {
      return le.H.useActionState(e, a, t);
    };
    O.useCallback = function (e, a) {
      return le.H.useCallback(e, a);
    };
    O.useContext = function (e) {
      return le.H.useContext(e);
    };
    O.useDebugValue = function () {};
    O.useDeferredValue = function (e, a) {
      return le.H.useDeferredValue(e, a);
    };
    O.useEffect = function (e, a) {
      return le.H.useEffect(e, a);
    };
    O.useEffectEvent = function (e) {
      return le.H.useEffectEvent(e);
    };
    O.useId = function () {
      return le.H.useId();
    };
    O.useImperativeHandle = function (e, a, t) {
      return le.H.useImperativeHandle(e, a, t);
    };
    O.useInsertionEffect = function (e, a) {
      return le.H.useInsertionEffect(e, a);
    };
    O.useLayoutEffect = function (e, a) {
      return le.H.useLayoutEffect(e, a);
    };
    O.useMemo = function (e, a) {
      return le.H.useMemo(e, a);
    };
    O.useOptimistic = function (e, a) {
      return le.H.useOptimistic(e, a);
    };
    O.useReducer = function (e, a, t) {
      return le.H.useReducer(e, a, t);
    };
    O.useRef = function (e) {
      return le.H.useRef(e);
    };
    O.useState = function (e) {
      return le.H.useState(e);
    };
    O.useSyncExternalStore = function (e, a, t) {
      return le.H.useSyncExternalStore(e, a, t);
    };
    O.useTransition = function () {
      return le.H.useTransition();
    };
    O.version = "19.2.6";
  });
  var ya = Da((Ay, kr) => {
    "use strict";
    kr.exports = Ar();
  });
  var wr = Da((Ee) => {
    "use strict";
    var Fg = ya();
    function Mr(e) {
      var a = "https://react.dev/errors/" + e;
      if (1 < arguments.length) {
        a += "?args[]=" + encodeURIComponent(arguments[1]);
        for (var t = 2; t < arguments.length; t++)
          a += "&args[]=" + encodeURIComponent(arguments[t]);
      }
      return (
        "Minified React error #" +
        e +
        "; visit " +
        a +
        " for the full message or use the non-minified dev environment for full errors and additional helpful warnings."
      );
    }
    function pt() {}
    var Oe = {
        d: {
          f: pt,
          r: function () {
            throw Error(Mr(522));
          },
          D: pt,
          C: pt,
          L: pt,
          m: pt,
          X: pt,
          S: pt,
          M: pt,
        },
        p: 0,
        findDOMNode: null,
      },
      Pg = Symbol.for("react.portal");
    function _g(e, a, t) {
      var l =
        3 < arguments.length && arguments[3] !== void 0 ? arguments[3] : null;
      return {
        $$typeof: Pg,
        key: l == null ? null : "" + l,
        children: e,
        containerInfo: a,
        implementation: t,
      };
    }
    var qu = Fg.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE;
    function id(e, a) {
      if (e === "font") return "";
      if (typeof a == "string") return a === "use-credentials" ? a : "";
    }
    Ee.__DOM_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE = Oe;
    Ee.createPortal = function (e, a) {
      var t =
        2 < arguments.length && arguments[2] !== void 0 ? arguments[2] : null;
      if (!a || (a.nodeType !== 1 && a.nodeType !== 9 && a.nodeType !== 11))
        throw Error(Mr(299));
      return _g(e, a, null, t);
    };
    Ee.flushSync = function (e) {
      var a = qu.T,
        t = Oe.p;
      try {
        if (((qu.T = null), (Oe.p = 2), e)) return e();
      } finally {
        ((qu.T = a), (Oe.p = t), Oe.d.f());
      }
    };
    Ee.preconnect = function (e, a) {
      typeof e == "string" &&
        (a
          ? ((a = a.crossOrigin),
            (a =
              typeof a == "string"
                ? a === "use-credentials"
                  ? a
                  : ""
                : void 0))
          : (a = null),
        Oe.d.C(e, a));
    };
    Ee.prefetchDNS = function (e) {
      typeof e == "string" && Oe.d.D(e);
    };
    Ee.preinit = function (e, a) {
      if (typeof e == "string" && a && typeof a.as == "string") {
        var t = a.as,
          l = id(t, a.crossOrigin),
          u = typeof a.integrity == "string" ? a.integrity : void 0,
          o = typeof a.fetchPriority == "string" ? a.fetchPriority : void 0;
        t === "style"
          ? Oe.d.S(e, typeof a.precedence == "string" ? a.precedence : void 0, {
              crossOrigin: l,
              integrity: u,
              fetchPriority: o,
            })
          : t === "script" &&
            Oe.d.X(e, {
              crossOrigin: l,
              integrity: u,
              fetchPriority: o,
              nonce: typeof a.nonce == "string" ? a.nonce : void 0,
            });
      }
    };
    Ee.preinitModule = function (e, a) {
      if (typeof e == "string")
        if (typeof a == "object" && a !== null) {
          if (a.as == null || a.as === "script") {
            var t = id(a.as, a.crossOrigin);
            Oe.d.M(e, {
              crossOrigin: t,
              integrity: typeof a.integrity == "string" ? a.integrity : void 0,
              nonce: typeof a.nonce == "string" ? a.nonce : void 0,
            });
          }
        } else a == null && Oe.d.M(e);
    };
    Ee.preload = function (e, a) {
      if (
        typeof e == "string" &&
        typeof a == "object" &&
        a !== null &&
        typeof a.as == "string"
      ) {
        var t = a.as,
          l = id(t, a.crossOrigin);
        Oe.d.L(e, t, {
          crossOrigin: l,
          integrity: typeof a.integrity == "string" ? a.integrity : void 0,
          nonce: typeof a.nonce == "string" ? a.nonce : void 0,
          type: typeof a.type == "string" ? a.type : void 0,
          fetchPriority:
            typeof a.fetchPriority == "string" ? a.fetchPriority : void 0,
          referrerPolicy:
            typeof a.referrerPolicy == "string" ? a.referrerPolicy : void 0,
          imageSrcSet:
            typeof a.imageSrcSet == "string" ? a.imageSrcSet : void 0,
          imageSizes: typeof a.imageSizes == "string" ? a.imageSizes : void 0,
          media: typeof a.media == "string" ? a.media : void 0,
        });
      }
    };
    Ee.preloadModule = function (e, a) {
      if (typeof e == "string")
        if (a) {
          var t = id(a.as, a.crossOrigin);
          Oe.d.m(e, {
            as: typeof a.as == "string" && a.as !== "script" ? a.as : void 0,
            crossOrigin: t,
            integrity: typeof a.integrity == "string" ? a.integrity : void 0,
          });
        } else Oe.d.m(e);
    };
    Ee.requestFormReset = function (e) {
      Oe.d.r(e);
    };
    Ee.unstable_batchedUpdates = function (e, a) {
      return e(a);
    };
    Ee.useFormState = function (e, a, t) {
      return qu.H.useFormState(e, a, t);
    };
    Ee.useFormStatus = function () {
      return qu.H.useHostTransitionStatus();
    };
    Ee.version = "19.2.6";
  });
  var Br = Da((My, Tr) => {
    "use strict";
    function Dr() {
      if (
        !(
          typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ > "u" ||
          typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE != "function"
        )
      )
        try {
          __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(Dr);
        } catch (e) {
          console.error(e);
        }
    }
    (Dr(), (Tr.exports = wr()));
  });
  var Gh = Da((Es) => {
    "use strict";
    var ye = pr(),
      lm = ya(),
      Gg = Br();
    function v(e) {
      var a = "https://react.dev/errors/" + e;
      if (1 < arguments.length) {
        a += "?args[]=" + encodeURIComponent(arguments[1]);
        for (var t = 2; t < arguments.length; t++)
          a += "&args[]=" + encodeURIComponent(arguments[t]);
      }
      return (
        "Minified React error #" +
        e +
        "; visit " +
        a +
        " for the full message or use the non-minified dev environment for full errors and additional helpful warnings."
      );
    }
    function um(e) {
      return !(
        !e ||
        (e.nodeType !== 1 && e.nodeType !== 9 && e.nodeType !== 11)
      );
    }
    function Io(e) {
      var a = e,
        t = e;
      if (e.alternate) for (; a.return; ) a = a.return;
      else {
        e = a;
        do ((a = e), (a.flags & 4098) !== 0 && (t = a.return), (e = a.return));
        while (e);
      }
      return a.tag === 3 ? t : null;
    }
    function om(e) {
      if (e.tag === 13) {
        var a = e.memoizedState;
        if (
          (a === null &&
            ((e = e.alternate), e !== null && (a = e.memoizedState)),
          a !== null)
        )
          return a.dehydrated;
      }
      return null;
    }
    function dm(e) {
      if (e.tag === 31) {
        var a = e.memoizedState;
        if (
          (a === null &&
            ((e = e.alternate), e !== null && (a = e.memoizedState)),
          a !== null)
        )
          return a.dehydrated;
      }
      return null;
    }
    function zr(e) {
      if (Io(e) !== e) throw Error(v(188));
    }
    function Vg(e) {
      var a = e.alternate;
      if (!a) {
        if (((a = Io(e)), a === null)) throw Error(v(188));
        return a !== e ? null : e;
      }
      for (var t = e, l = a; ; ) {
        var u = t.return;
        if (u === null) break;
        var o = u.alternate;
        if (o === null) {
          if (((l = u.return), l !== null)) {
            t = l;
            continue;
          }
          break;
        }
        if (u.child === o.child) {
          for (o = u.child; o; ) {
            if (o === t) return (zr(u), e);
            if (o === l) return (zr(u), a);
            o = o.sibling;
          }
          throw Error(v(188));
        }
        if (t.return !== l.return) ((t = u), (l = o));
        else {
          for (var d = !1, f = u.child; f; ) {
            if (f === t) {
              ((d = !0), (t = u), (l = o));
              break;
            }
            if (f === l) {
              ((d = !0), (l = u), (t = o));
              break;
            }
            f = f.sibling;
          }
          if (!d) {
            for (f = o.child; f; ) {
              if (f === t) {
                ((d = !0), (t = o), (l = u));
                break;
              }
              if (f === l) {
                ((d = !0), (l = o), (t = u));
                break;
              }
              f = f.sibling;
            }
            if (!d) throw Error(v(189));
          }
        }
        if (t.alternate !== l) throw Error(v(190));
      }
      if (t.tag !== 3) throw Error(v(188));
      return t.stateNode.current === t ? e : a;
    }
    function sm(e) {
      var a = e.tag;
      if (a === 5 || a === 26 || a === 27 || a === 6) return e;
      for (e = e.child; e !== null; ) {
        if (((a = sm(e)), a !== null)) return a;
        e = e.sibling;
      }
      return null;
    }
    var de = Object.assign,
      Xg = Symbol.for("react.element"),
      rd = Symbol.for("react.transitional.element"),
      Vu = Symbol.for("react.portal"),
      Pl = Symbol.for("react.fragment"),
      fm = Symbol.for("react.strict_mode"),
      Zf = Symbol.for("react.profiler"),
      nm = Symbol.for("react.consumer"),
      Ja = Symbol.for("react.context"),
      Vn = Symbol.for("react.forward_ref"),
      Qf = Symbol.for("react.suspense"),
      Kf = Symbol.for("react.suspense_list"),
      Xn = Symbol.for("react.memo"),
      ht = Symbol.for("react.lazy"),
      Jf = Symbol.for("react.activity"),
      Yg = Symbol.for("react.memo_cache_sentinel"),
      Rr = Symbol.iterator;
    function Nu(e) {
      return e === null || typeof e != "object"
        ? null
        : ((e = (Rr && e[Rr]) || e["@@iterator"]),
          typeof e == "function" ? e : null);
    }
    var jg = Symbol.for("react.client.reference");
    function Wf(e) {
      if (e == null) return null;
      if (typeof e == "function")
        return e.$$typeof === jg ? null : e.displayName || e.name || null;
      if (typeof e == "string") return e;
      switch (e) {
        case Pl:
          return "Fragment";
        case Zf:
          return "Profiler";
        case fm:
          return "StrictMode";
        case Qf:
          return "Suspense";
        case Kf:
          return "SuspenseList";
        case Jf:
          return "Activity";
      }
      if (typeof e == "object")
        switch (e.$$typeof) {
          case Vu:
            return "Portal";
          case Ja:
            return e.displayName || "Context";
          case nm:
            return (e._context.displayName || "Context") + ".Consumer";
          case Vn:
            var a = e.render;
            return (
              (e = e.displayName),
              e ||
                ((e = a.displayName || a.name || ""),
                (e = e !== "" ? "ForwardRef(" + e + ")" : "ForwardRef")),
              e
            );
          case Xn:
            return (
              (a = e.displayName || null),
              a !== null ? a : Wf(e.type) || "Memo"
            );
          case ht:
            ((a = e._payload), (e = e._init));
            try {
              return Wf(e(a));
            } catch {}
        }
      return null;
    }
    var Xu = Array.isArray,
      R = lm.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE,
      K = Gg.__DOM_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE,
      nl = { pending: !1, data: null, method: null, action: null },
      $f = [],
      _l = -1;
    function Ea(e) {
      return { current: e };
    }
    function Ie(e) {
      0 > _l || ((e.current = $f[_l]), ($f[_l] = null), _l--);
    }
    function te(e, a) {
      (_l++, ($f[_l] = e.current), (e.current = a));
    }
    var Oa = Ea(null),
      no = Ea(null),
      kt = Ea(null),
      Vd = Ea(null);
    function Xd(e, a) {
      switch ((te(kt, a), te(no, e), te(Oa, null), a.nodeType)) {
        case 9:
        case 11:
          e = (e = a.documentElement) && (e = e.namespaceURI) ? Fc(e) : 0;
          break;
        default:
          if (((e = a.tagName), (a = a.namespaceURI)))
            ((a = Fc(a)), (e = Dh(a, e)));
          else
            switch (e) {
              case "svg":
                e = 1;
                break;
              case "math":
                e = 2;
                break;
              default:
                e = 0;
            }
      }
      (Ie(Oa), te(Oa, e));
    }
    function du() {
      (Ie(Oa), Ie(no), Ie(kt));
    }
    function en(e) {
      e.memoizedState !== null && te(Vd, e);
      var a = Oa.current,
        t = Dh(a, e.type);
      a !== t && (te(no, e), te(Oa, t));
    }
    function Yd(e) {
      (no.current === e && (Ie(Oa), Ie(no)),
        Vd.current === e && (Ie(Vd), (yo._currentValue = nl)));
    }
    var hf, Or;
    function ol(e) {
      if (hf === void 0)
        try {
          throw Error();
        } catch (t) {
          var a = t.stack.trim().match(/\n( *(at )?)/);
          ((hf = (a && a[1]) || ""),
            (Or =
              -1 <
              t.stack.indexOf(`
    at`)
                ? " (<anonymous>)"
                : -1 < t.stack.indexOf("@")
                  ? "@unknown:0:0"
                  : ""));
        }
      return (
        `
` +
        hf +
        e +
        Or
      );
    }
    var Lf = !1;
    function gf(e, a) {
      if (!e || Lf) return "";
      Lf = !0;
      var t = Error.prepareStackTrace;
      Error.prepareStackTrace = void 0;
      try {
        var l = {
          DetermineComponentFrameRoot: function () {
            try {
              if (a) {
                var x = function () {
                  throw Error();
                };
                if (
                  (Object.defineProperty(x.prototype, "props", {
                    set: function () {
                      throw Error();
                    },
                  }),
                  typeof Reflect == "object" && Reflect.construct)
                ) {
                  try {
                    Reflect.construct(x, []);
                  } catch (L) {
                    var m = L;
                  }
                  Reflect.construct(e, [], x);
                } else {
                  try {
                    x.call();
                  } catch (L) {
                    m = L;
                  }
                  e.call(x.prototype);
                }
              } else {
                try {
                  throw Error();
                } catch (L) {
                  m = L;
                }
                (x = e()) &&
                  typeof x.catch == "function" &&
                  x.catch(function () {});
              }
            } catch (L) {
              if (L && m && typeof L.stack == "string")
                return [L.stack, m.stack];
            }
            return [null, null];
          },
        };
        l.DetermineComponentFrameRoot.displayName =
          "DetermineComponentFrameRoot";
        var u = Object.getOwnPropertyDescriptor(
          l.DetermineComponentFrameRoot,
          "name",
        );
        u &&
          u.configurable &&
          Object.defineProperty(l.DetermineComponentFrameRoot, "name", {
            value: "DetermineComponentFrameRoot",
          });
        var o = l.DetermineComponentFrameRoot(),
          d = o[0],
          f = o[1];
        if (d && f) {
          var n = d.split(`
`),
            p = f.split(`
`);
          for (
            u = l = 0;
            l < n.length && !n[l].includes("DetermineComponentFrameRoot");
          )
            l++;
          for (
            ;
            u < p.length && !p[u].includes("DetermineComponentFrameRoot");
          )
            u++;
          if (l === n.length || u === p.length)
            for (
              l = n.length - 1, u = p.length - 1;
              1 <= l && 0 <= u && n[l] !== p[u];
            )
              u--;
          for (; 1 <= l && 0 <= u; l--, u--)
            if (n[l] !== p[u]) {
              if (l !== 1 || u !== 1)
                do
                  if ((l--, u--, 0 > u || n[l] !== p[u])) {
                    var h =
                      `
` + n[l].replace(" at new ", " at ");
                    return (
                      e.displayName &&
                        h.includes("<anonymous>") &&
                        (h = h.replace("<anonymous>", e.displayName)),
                      h
                    );
                  }
                while (1 <= l && 0 <= u);
              break;
            }
        }
      } finally {
        ((Lf = !1), (Error.prepareStackTrace = t));
      }
      return (t = e ? e.displayName || e.name : "") ? ol(t) : "";
    }
    function Zg(e, a) {
      switch (e.tag) {
        case 26:
        case 27:
        case 5:
          return ol(e.type);
        case 16:
          return ol("Lazy");
        case 13:
          return e.child !== a && a !== null
            ? ol("Suspense Fallback")
            : ol("Suspense");
        case 19:
          return ol("SuspenseList");
        case 0:
        case 15:
          return gf(e.type, !1);
        case 11:
          return gf(e.type.render, !1);
        case 1:
          return gf(e.type, !0);
        case 31:
          return ol("Activity");
        default:
          return "";
      }
    }
    function Er(e) {
      try {
        var a = "",
          t = null;
        do ((a += Zg(e, t)), (t = e), (e = e.return));
        while (e);
        return a;
      } catch (l) {
        return (
          `
Error generating stack: ` +
          l.message +
          `
` +
          l.stack
        );
      }
    }
    var an = Object.prototype.hasOwnProperty,
      Yn = ye.unstable_scheduleCallback,
      xf = ye.unstable_cancelCallback,
      Qg = ye.unstable_shouldYield,
      Kg = ye.unstable_requestPaint,
      Je = ye.unstable_now,
      Jg = ye.unstable_getCurrentPriorityLevel,
      im = ye.unstable_ImmediatePriority,
      rm = ye.unstable_UserBlockingPriority,
      jd = ye.unstable_NormalPriority,
      Wg = ye.unstable_LowPriority,
      cm = ye.unstable_IdlePriority,
      $g = ye.log,
      ex = ye.unstable_setDisableYieldValue,
      bo = null,
      We = null;
    function St(e) {
      if (
        (typeof $g == "function" && ex(e),
        We && typeof We.setStrictMode == "function")
      )
        try {
          We.setStrictMode(bo, e);
        } catch {}
    }
    var $e = Math.clz32 ? Math.clz32 : lx,
      ax = Math.log,
      tx = Math.LN2;
    function lx(e) {
      return ((e >>>= 0), e === 0 ? 32 : (31 - ((ax(e) / tx) | 0)) | 0);
    }
    var cd = 256,
      md = 262144,
      pd = 4194304;
    function dl(e) {
      var a = e & 42;
      if (a !== 0) return a;
      switch (e & -e) {
        case 1:
          return 1;
        case 2:
          return 2;
        case 4:
          return 4;
        case 8:
          return 8;
        case 16:
          return 16;
        case 32:
          return 32;
        case 64:
          return 64;
        case 128:
          return 128;
        case 256:
        case 512:
        case 1024:
        case 2048:
        case 4096:
        case 8192:
        case 16384:
        case 32768:
        case 65536:
        case 131072:
          return e & 261888;
        case 262144:
        case 524288:
        case 1048576:
        case 2097152:
          return e & 3932160;
        case 4194304:
        case 8388608:
        case 16777216:
        case 33554432:
          return e & 62914560;
        case 67108864:
          return 67108864;
        case 134217728:
          return 134217728;
        case 268435456:
          return 268435456;
        case 536870912:
          return 536870912;
        case 1073741824:
          return 0;
        default:
          return e;
      }
    }
    function Cs(e, a, t) {
      var l = e.pendingLanes;
      if (l === 0) return 0;
      var u = 0,
        o = e.suspendedLanes,
        d = e.pingedLanes;
      e = e.warmLanes;
      var f = l & 134217727;
      return (
        f !== 0
          ? ((l = f & ~o),
            l !== 0
              ? (u = dl(l))
              : ((d &= f),
                d !== 0
                  ? (u = dl(d))
                  : t || ((t = f & ~e), t !== 0 && (u = dl(t)))))
          : ((f = l & ~o),
            f !== 0
              ? (u = dl(f))
              : d !== 0
                ? (u = dl(d))
                : t || ((t = l & ~e), t !== 0 && (u = dl(t)))),
        u === 0
          ? 0
          : a !== 0 &&
              a !== u &&
              (a & o) === 0 &&
              ((o = u & -u),
              (t = a & -a),
              o >= t || (o === 32 && (t & 4194048) !== 0))
            ? a
            : u
      );
    }
    function Ao(e, a) {
      return (e.pendingLanes & ~(e.suspendedLanes & ~e.pingedLanes) & a) === 0;
    }
    function ux(e, a) {
      switch (e) {
        case 1:
        case 2:
        case 4:
        case 8:
        case 64:
          return a + 250;
        case 16:
        case 32:
        case 128:
        case 256:
        case 512:
        case 1024:
        case 2048:
        case 4096:
        case 8192:
        case 16384:
        case 32768:
        case 65536:
        case 131072:
        case 262144:
        case 524288:
        case 1048576:
        case 2097152:
          return a + 5e3;
        case 4194304:
        case 8388608:
        case 16777216:
        case 33554432:
          return -1;
        case 67108864:
        case 134217728:
        case 268435456:
        case 536870912:
        case 1073741824:
          return -1;
        default:
          return -1;
      }
    }
    function mm() {
      var e = pd;
      return ((pd <<= 1), (pd & 62914560) === 0 && (pd = 4194304), e);
    }
    function Cf(e) {
      for (var a = [], t = 0; 31 > t; t++) a.push(e);
      return a;
    }
    function ko(e, a) {
      ((e.pendingLanes |= a),
        a !== 268435456 &&
          ((e.suspendedLanes = 0), (e.pingedLanes = 0), (e.warmLanes = 0)));
    }
    function ox(e, a, t, l, u, o) {
      var d = e.pendingLanes;
      ((e.pendingLanes = t),
        (e.suspendedLanes = 0),
        (e.pingedLanes = 0),
        (e.warmLanes = 0),
        (e.expiredLanes &= t),
        (e.entangledLanes &= t),
        (e.errorRecoveryDisabledLanes &= t),
        (e.shellSuspendCounter = 0));
      var f = e.entanglements,
        n = e.expirationTimes,
        p = e.hiddenUpdates;
      for (t = d & ~t; 0 < t; ) {
        var h = 31 - $e(t),
          x = 1 << h;
        ((f[h] = 0), (n[h] = -1));
        var m = p[h];
        if (m !== null)
          for (p[h] = null, h = 0; h < m.length; h++) {
            var L = m[h];
            L !== null && (L.lane &= -536870913);
          }
        t &= ~x;
      }
      (l !== 0 && pm(e, l, 0),
        o !== 0 &&
          u === 0 &&
          e.tag !== 0 &&
          (e.suspendedLanes |= o & ~(d & ~a)));
    }
    function pm(e, a, t) {
      ((e.pendingLanes |= a), (e.suspendedLanes &= ~a));
      var l = 31 - $e(a);
      ((e.entangledLanes |= a),
        (e.entanglements[l] = e.entanglements[l] | 1073741824 | (t & 261930)));
    }
    function hm(e, a) {
      var t = (e.entangledLanes |= a);
      for (e = e.entanglements; t; ) {
        var l = 31 - $e(t),
          u = 1 << l;
        ((u & a) | (e[l] & a) && (e[l] |= a), (t &= ~u));
      }
    }
    function Lm(e, a) {
      var t = a & -a;
      return (
        (t = (t & 42) !== 0 ? 1 : jn(t)),
        (t & (e.suspendedLanes | a)) !== 0 ? 0 : t
      );
    }
    function jn(e) {
      switch (e) {
        case 2:
          e = 1;
          break;
        case 8:
          e = 4;
          break;
        case 32:
          e = 16;
          break;
        case 256:
        case 512:
        case 1024:
        case 2048:
        case 4096:
        case 8192:
        case 16384:
        case 32768:
        case 65536:
        case 131072:
        case 262144:
        case 524288:
        case 1048576:
        case 2097152:
        case 4194304:
        case 8388608:
        case 16777216:
        case 33554432:
          e = 128;
          break;
        case 268435456:
          e = 134217728;
          break;
        default:
          e = 0;
      }
      return e;
    }
    function Zn(e) {
      return (
        (e &= -e),
        2 < e ? (8 < e ? ((e & 134217727) !== 0 ? 32 : 268435456) : 8) : 2
      );
    }
    function gm() {
      var e = K.p;
      return e !== 0 ? e : ((e = window.event), e === void 0 ? 32 : Fh(e.type));
    }
    function qr(e, a) {
      var t = K.p;
      try {
        return ((K.p = e), a());
      } finally {
        K.p = t;
      }
    }
    var Ht = Math.random().toString(36).slice(2),
      Ae = "__reactFiber$" + Ht,
      Ge = "__reactProps$" + Ht,
      gu = "__reactContainer$" + Ht,
      tn = "__reactEvents$" + Ht,
      dx = "__reactListeners$" + Ht,
      sx = "__reactHandles$" + Ht,
      Nr = "__reactResources$" + Ht,
      Mo = "__reactMarker$" + Ht;
    function Qn(e) {
      (delete e[Ae], delete e[Ge], delete e[tn], delete e[dx], delete e[sx]);
    }
    function Gl(e) {
      var a = e[Ae];
      if (a) return a;
      for (var t = e.parentNode; t; ) {
        if ((a = t[gu] || t[Ae])) {
          if (
            ((t = a.alternate),
            a.child !== null || (t !== null && t.child !== null))
          )
            for (e = Xc(e); e !== null; ) {
              if ((t = e[Ae])) return t;
              e = Xc(e);
            }
          return a;
        }
        ((e = t), (t = e.parentNode));
      }
      return null;
    }
    function xu(e) {
      if ((e = e[Ae] || e[gu])) {
        var a = e.tag;
        if (
          a === 5 ||
          a === 6 ||
          a === 13 ||
          a === 31 ||
          a === 26 ||
          a === 27 ||
          a === 3
        )
          return e;
      }
      return null;
    }
    function Yu(e) {
      var a = e.tag;
      if (a === 5 || a === 26 || a === 27 || a === 6) return e.stateNode;
      throw Error(v(33));
    }
    function $l(e) {
      var a = e[Nr];
      return (
        a ||
          (a = e[Nr] =
            { hoistableStyles: new Map(), hoistableScripts: new Map() }),
        a
      );
    }
    function ve(e) {
      e[Mo] = !0;
    }
    var xm = new Set(),
      Cm = {};
    function Cl(e, a) {
      (su(e, a), su(e + "Capture", a));
    }
    function su(e, a) {
      for (Cm[e] = a, e = 0; e < a.length; e++) xm.add(a[e]);
    }
    var fx = RegExp(
        "^[:A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD][:A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD\\-.0-9\\u00B7\\u0300-\\u036F\\u203F-\\u2040]*$",
      ),
      Ur = {},
      Hr = {};
    function nx(e) {
      return an.call(Hr, e)
        ? !0
        : an.call(Ur, e)
          ? !1
          : fx.test(e)
            ? (Hr[e] = !0)
            : ((Ur[e] = !0), !1);
    }
    function Dd(e, a, t) {
      if (nx(a))
        if (t === null) e.removeAttribute(a);
        else {
          switch (typeof t) {
            case "undefined":
            case "function":
            case "symbol":
              e.removeAttribute(a);
              return;
            case "boolean":
              var l = a.toLowerCase().slice(0, 5);
              if (l !== "data-" && l !== "aria-") {
                e.removeAttribute(a);
                return;
              }
          }
          e.setAttribute(a, "" + t);
        }
    }
    function hd(e, a, t) {
      if (t === null) e.removeAttribute(a);
      else {
        switch (typeof t) {
          case "undefined":
          case "function":
          case "symbol":
          case "boolean":
            e.removeAttribute(a);
            return;
        }
        e.setAttribute(a, "" + t);
      }
    }
    function Va(e, a, t, l) {
      if (l === null) e.removeAttribute(t);
      else {
        switch (typeof l) {
          case "undefined":
          case "function":
          case "symbol":
          case "boolean":
            e.removeAttribute(t);
            return;
        }
        e.setAttributeNS(a, t, "" + l);
      }
    }
    function ia(e) {
      switch (typeof e) {
        case "bigint":
        case "boolean":
        case "number":
        case "string":
        case "undefined":
          return e;
        case "object":
          return e;
        default:
          return "";
      }
    }
    function ym(e) {
      var a = e.type;
      return (
        (e = e.nodeName) &&
        e.toLowerCase() === "input" &&
        (a === "checkbox" || a === "radio")
      );
    }
    function ix(e, a, t) {
      var l = Object.getOwnPropertyDescriptor(e.constructor.prototype, a);
      if (
        !e.hasOwnProperty(a) &&
        typeof l < "u" &&
        typeof l.get == "function" &&
        typeof l.set == "function"
      ) {
        var u = l.get,
          o = l.set;
        return (
          Object.defineProperty(e, a, {
            configurable: !0,
            get: function () {
              return u.call(this);
            },
            set: function (d) {
              ((t = "" + d), o.call(this, d));
            },
          }),
          Object.defineProperty(e, a, { enumerable: l.enumerable }),
          {
            getValue: function () {
              return t;
            },
            setValue: function (d) {
              t = "" + d;
            },
            stopTracking: function () {
              ((e._valueTracker = null), delete e[a]);
            },
          }
        );
      }
    }
    function ln(e) {
      if (!e._valueTracker) {
        var a = ym(e) ? "checked" : "value";
        e._valueTracker = ix(e, a, "" + e[a]);
      }
    }
    function Sm(e) {
      if (!e) return !1;
      var a = e._valueTracker;
      if (!a) return !0;
      var t = a.getValue(),
        l = "";
      return (
        e && (l = ym(e) ? (e.checked ? "true" : "false") : e.value),
        (e = l),
        e !== t ? (a.setValue(e), !0) : !1
      );
    }
    function Zd(e) {
      if (
        ((e = e || (typeof document < "u" ? document : void 0)), typeof e > "u")
      )
        return null;
      try {
        return e.activeElement || e.body;
      } catch {
        return e.body;
      }
    }
    var rx = /[\n"\\]/g;
    function ma(e) {
      return e.replace(rx, function (a) {
        return "\\" + a.charCodeAt(0).toString(16) + " ";
      });
    }
    function un(e, a, t, l, u, o, d, f) {
      ((e.name = ""),
        d != null &&
        typeof d != "function" &&
        typeof d != "symbol" &&
        typeof d != "boolean"
          ? (e.type = d)
          : e.removeAttribute("type"),
        a != null
          ? d === "number"
            ? ((a === 0 && e.value === "") || e.value != a) &&
              (e.value = "" + ia(a))
            : e.value !== "" + ia(a) && (e.value = "" + ia(a))
          : (d !== "submit" && d !== "reset") || e.removeAttribute("value"),
        a != null
          ? on(e, d, ia(a))
          : t != null
            ? on(e, d, ia(t))
            : l != null && e.removeAttribute("value"),
        u == null && o != null && (e.defaultChecked = !!o),
        u != null &&
          (e.checked = u && typeof u != "function" && typeof u != "symbol"),
        f != null &&
        typeof f != "function" &&
        typeof f != "symbol" &&
        typeof f != "boolean"
          ? (e.name = "" + ia(f))
          : e.removeAttribute("name"));
    }
    function vm(e, a, t, l, u, o, d, f) {
      if (
        (o != null &&
          typeof o != "function" &&
          typeof o != "symbol" &&
          typeof o != "boolean" &&
          (e.type = o),
        a != null || t != null)
      ) {
        if (!((o !== "submit" && o !== "reset") || a != null)) {
          ln(e);
          return;
        }
        ((t = t != null ? "" + ia(t) : ""),
          (a = a != null ? "" + ia(a) : t),
          f || a === e.value || (e.value = a),
          (e.defaultValue = a));
      }
      ((l = l ?? u),
        (l = typeof l != "function" && typeof l != "symbol" && !!l),
        (e.checked = f ? e.checked : !!l),
        (e.defaultChecked = !!l),
        d != null &&
          typeof d != "function" &&
          typeof d != "symbol" &&
          typeof d != "boolean" &&
          (e.name = d),
        ln(e));
    }
    function on(e, a, t) {
      (a === "number" && Zd(e.ownerDocument) === e) ||
        e.defaultValue === "" + t ||
        (e.defaultValue = "" + t);
    }
    function eu(e, a, t, l) {
      if (((e = e.options), a)) {
        a = {};
        for (var u = 0; u < t.length; u++) a["$" + t[u]] = !0;
        for (t = 0; t < e.length; t++)
          ((u = a.hasOwnProperty("$" + e[t].value)),
            e[t].selected !== u && (e[t].selected = u),
            u && l && (e[t].defaultSelected = !0));
      } else {
        for (t = "" + ia(t), a = null, u = 0; u < e.length; u++) {
          if (e[u].value === t) {
            ((e[u].selected = !0), l && (e[u].defaultSelected = !0));
            return;
          }
          a !== null || e[u].disabled || (a = e[u]);
        }
        a !== null && (a.selected = !0);
      }
    }
    function Im(e, a, t) {
      if (
        a != null &&
        ((a = "" + ia(a)), a !== e.value && (e.value = a), t == null)
      ) {
        e.defaultValue !== a && (e.defaultValue = a);
        return;
      }
      e.defaultValue = t != null ? "" + ia(t) : "";
    }
    function bm(e, a, t, l) {
      if (a == null) {
        if (l != null) {
          if (t != null) throw Error(v(92));
          if (Xu(l)) {
            if (1 < l.length) throw Error(v(93));
            l = l[0];
          }
          t = l;
        }
        (t == null && (t = ""), (a = t));
      }
      ((t = ia(a)),
        (e.defaultValue = t),
        (l = e.textContent),
        l === t && l !== "" && l !== null && (e.value = l),
        ln(e));
    }
    function fu(e, a) {
      if (a) {
        var t = e.firstChild;
        if (t && t === e.lastChild && t.nodeType === 3) {
          t.nodeValue = a;
          return;
        }
      }
      e.textContent = a;
    }
    var cx = new Set(
      "animationIterationCount aspectRatio borderImageOutset borderImageSlice borderImageWidth boxFlex boxFlexGroup boxOrdinalGroup columnCount columns flex flexGrow flexPositive flexShrink flexNegative flexOrder gridArea gridRow gridRowEnd gridRowSpan gridRowStart gridColumn gridColumnEnd gridColumnSpan gridColumnStart fontWeight lineClamp lineHeight opacity order orphans scale tabSize widows zIndex zoom fillOpacity floodOpacity stopOpacity strokeDasharray strokeDashoffset strokeMiterlimit strokeOpacity strokeWidth MozAnimationIterationCount MozBoxFlex MozBoxFlexGroup MozLineClamp msAnimationIterationCount msFlex msZoom msFlexGrow msFlexNegative msFlexOrder msFlexPositive msFlexShrink msGridColumn msGridColumnSpan msGridRow msGridRowSpan WebkitAnimationIterationCount WebkitBoxFlex WebKitBoxFlexGroup WebkitBoxOrdinalGroup WebkitColumnCount WebkitColumns WebkitFlex WebkitFlexGrow WebkitFlexPositive WebkitFlexShrink WebkitLineClamp".split(
        " ",
      ),
    );
    function Fr(e, a, t) {
      var l = a.indexOf("--") === 0;
      t == null || typeof t == "boolean" || t === ""
        ? l
          ? e.setProperty(a, "")
          : a === "float"
            ? (e.cssFloat = "")
            : (e[a] = "")
        : l
          ? e.setProperty(a, t)
          : typeof t != "number" || t === 0 || cx.has(a)
            ? a === "float"
              ? (e.cssFloat = t)
              : (e[a] = ("" + t).trim())
            : (e[a] = t + "px");
    }
    function Am(e, a, t) {
      if (a != null && typeof a != "object") throw Error(v(62));
      if (((e = e.style), t != null)) {
        for (var l in t)
          !t.hasOwnProperty(l) ||
            (a != null && a.hasOwnProperty(l)) ||
            (l.indexOf("--") === 0
              ? e.setProperty(l, "")
              : l === "float"
                ? (e.cssFloat = "")
                : (e[l] = ""));
        for (var u in a)
          ((l = a[u]), a.hasOwnProperty(u) && t[u] !== l && Fr(e, u, l));
      } else for (var o in a) a.hasOwnProperty(o) && Fr(e, o, a[o]);
    }
    function Kn(e) {
      if (e.indexOf("-") === -1) return !1;
      switch (e) {
        case "annotation-xml":
        case "color-profile":
        case "font-face":
        case "font-face-src":
        case "font-face-uri":
        case "font-face-format":
        case "font-face-name":
        case "missing-glyph":
          return !1;
        default:
          return !0;
      }
    }
    var mx = new Map([
        ["acceptCharset", "accept-charset"],
        ["htmlFor", "for"],
        ["httpEquiv", "http-equiv"],
        ["crossOrigin", "crossorigin"],
        ["accentHeight", "accent-height"],
        ["alignmentBaseline", "alignment-baseline"],
        ["arabicForm", "arabic-form"],
        ["baselineShift", "baseline-shift"],
        ["capHeight", "cap-height"],
        ["clipPath", "clip-path"],
        ["clipRule", "clip-rule"],
        ["colorInterpolation", "color-interpolation"],
        ["colorInterpolationFilters", "color-interpolation-filters"],
        ["colorProfile", "color-profile"],
        ["colorRendering", "color-rendering"],
        ["dominantBaseline", "dominant-baseline"],
        ["enableBackground", "enable-background"],
        ["fillOpacity", "fill-opacity"],
        ["fillRule", "fill-rule"],
        ["floodColor", "flood-color"],
        ["floodOpacity", "flood-opacity"],
        ["fontFamily", "font-family"],
        ["fontSize", "font-size"],
        ["fontSizeAdjust", "font-size-adjust"],
        ["fontStretch", "font-stretch"],
        ["fontStyle", "font-style"],
        ["fontVariant", "font-variant"],
        ["fontWeight", "font-weight"],
        ["glyphName", "glyph-name"],
        ["glyphOrientationHorizontal", "glyph-orientation-horizontal"],
        ["glyphOrientationVertical", "glyph-orientation-vertical"],
        ["horizAdvX", "horiz-adv-x"],
        ["horizOriginX", "horiz-origin-x"],
        ["imageRendering", "image-rendering"],
        ["letterSpacing", "letter-spacing"],
        ["lightingColor", "lighting-color"],
        ["markerEnd", "marker-end"],
        ["markerMid", "marker-mid"],
        ["markerStart", "marker-start"],
        ["overlinePosition", "overline-position"],
        ["overlineThickness", "overline-thickness"],
        ["paintOrder", "paint-order"],
        ["panose-1", "panose-1"],
        ["pointerEvents", "pointer-events"],
        ["renderingIntent", "rendering-intent"],
        ["shapeRendering", "shape-rendering"],
        ["stopColor", "stop-color"],
        ["stopOpacity", "stop-opacity"],
        ["strikethroughPosition", "strikethrough-position"],
        ["strikethroughThickness", "strikethrough-thickness"],
        ["strokeDasharray", "stroke-dasharray"],
        ["strokeDashoffset", "stroke-dashoffset"],
        ["strokeLinecap", "stroke-linecap"],
        ["strokeLinejoin", "stroke-linejoin"],
        ["strokeMiterlimit", "stroke-miterlimit"],
        ["strokeOpacity", "stroke-opacity"],
        ["strokeWidth", "stroke-width"],
        ["textAnchor", "text-anchor"],
        ["textDecoration", "text-decoration"],
        ["textRendering", "text-rendering"],
        ["transformOrigin", "transform-origin"],
        ["underlinePosition", "underline-position"],
        ["underlineThickness", "underline-thickness"],
        ["unicodeBidi", "unicode-bidi"],
        ["unicodeRange", "unicode-range"],
        ["unitsPerEm", "units-per-em"],
        ["vAlphabetic", "v-alphabetic"],
        ["vHanging", "v-hanging"],
        ["vIdeographic", "v-ideographic"],
        ["vMathematical", "v-mathematical"],
        ["vectorEffect", "vector-effect"],
        ["vertAdvY", "vert-adv-y"],
        ["vertOriginX", "vert-origin-x"],
        ["vertOriginY", "vert-origin-y"],
        ["wordSpacing", "word-spacing"],
        ["writingMode", "writing-mode"],
        ["xmlnsXlink", "xmlns:xlink"],
        ["xHeight", "x-height"],
      ]),
      px =
        /^[\u0000-\u001F ]*j[\r\n\t]*a[\r\n\t]*v[\r\n\t]*a[\r\n\t]*s[\r\n\t]*c[\r\n\t]*r[\r\n\t]*i[\r\n\t]*p[\r\n\t]*t[\r\n\t]*:/i;
    function Td(e) {
      return px.test("" + e)
        ? "javascript:throw new Error('React has blocked a javascript: URL as a security precaution.')"
        : e;
    }
    function Wa() {}
    var dn = null;
    function Jn(e) {
      return (
        (e = e.target || e.srcElement || window),
        e.correspondingUseElement && (e = e.correspondingUseElement),
        e.nodeType === 3 ? e.parentNode : e
      );
    }
    var Vl = null,
      au = null;
    function Pr(e) {
      var a = xu(e);
      if (a && (e = a.stateNode)) {
        var t = e[Ge] || null;
        e: switch (((e = a.stateNode), a.type)) {
          case "input":
            if (
              (un(
                e,
                t.value,
                t.defaultValue,
                t.defaultValue,
                t.checked,
                t.defaultChecked,
                t.type,
                t.name,
              ),
              (a = t.name),
              t.type === "radio" && a != null)
            ) {
              for (t = e; t.parentNode; ) t = t.parentNode;
              for (
                t = t.querySelectorAll(
                  'input[name="' + ma("" + a) + '"][type="radio"]',
                ),
                  a = 0;
                a < t.length;
                a++
              ) {
                var l = t[a];
                if (l !== e && l.form === e.form) {
                  var u = l[Ge] || null;
                  if (!u) throw Error(v(90));
                  un(
                    l,
                    u.value,
                    u.defaultValue,
                    u.defaultValue,
                    u.checked,
                    u.defaultChecked,
                    u.type,
                    u.name,
                  );
                }
              }
              for (a = 0; a < t.length; a++)
                ((l = t[a]), l.form === e.form && Sm(l));
            }
            break e;
          case "textarea":
            Im(e, t.value, t.defaultValue);
            break e;
          case "select":
            ((a = t.value), a != null && eu(e, !!t.multiple, a, !1));
        }
      }
    }
    var yf = !1;
    function km(e, a, t) {
      if (yf) return e(a, t);
      yf = !0;
      try {
        var l = e(a);
        return l;
      } finally {
        if (
          ((yf = !1),
          (Vl !== null || au !== null) &&
            (Bs(), Vl && ((a = Vl), (e = au), (au = Vl = null), Pr(a), e)))
        )
          for (a = 0; a < e.length; a++) Pr(e[a]);
      }
    }
    function io(e, a) {
      var t = e.stateNode;
      if (t === null) return null;
      var l = t[Ge] || null;
      if (l === null) return null;
      t = l[a];
      e: switch (a) {
        case "onClick":
        case "onClickCapture":
        case "onDoubleClick":
        case "onDoubleClickCapture":
        case "onMouseDown":
        case "onMouseDownCapture":
        case "onMouseMove":
        case "onMouseMoveCapture":
        case "onMouseUp":
        case "onMouseUpCapture":
        case "onMouseEnter":
          ((l = !l.disabled) ||
            ((e = e.type),
            (l = !(
              e === "button" ||
              e === "input" ||
              e === "select" ||
              e === "textarea"
            ))),
            (e = !l));
          break e;
        default:
          e = !1;
      }
      if (e) return null;
      if (t && typeof t != "function") throw Error(v(231, a, typeof t));
      return t;
    }
    var lt = !(
        typeof window > "u" ||
        typeof window.document > "u" ||
        typeof window.document.createElement > "u"
      ),
      sn = !1;
    if (lt)
      try {
        ((ql = {}),
          Object.defineProperty(ql, "passive", {
            get: function () {
              sn = !0;
            },
          }),
          window.addEventListener("test", ql, ql),
          window.removeEventListener("test", ql, ql));
      } catch {
        sn = !1;
      }
    var ql,
      vt = null,
      Wn = null,
      Bd = null;
    function Mm() {
      if (Bd) return Bd;
      var e,
        a = Wn,
        t = a.length,
        l,
        u = "value" in vt ? vt.value : vt.textContent,
        o = u.length;
      for (e = 0; e < t && a[e] === u[e]; e++);
      var d = t - e;
      for (l = 1; l <= d && a[t - l] === u[o - l]; l++);
      return (Bd = u.slice(e, 1 < l ? 1 - l : void 0));
    }
    function zd(e) {
      var a = e.keyCode;
      return (
        "charCode" in e
          ? ((e = e.charCode), e === 0 && a === 13 && (e = 13))
          : (e = a),
        e === 10 && (e = 13),
        32 <= e || e === 13 ? e : 0
      );
    }
    function Ld() {
      return !0;
    }
    function _r() {
      return !1;
    }
    function Ve(e) {
      function a(t, l, u, o, d) {
        ((this._reactName = t),
          (this._targetInst = u),
          (this.type = l),
          (this.nativeEvent = o),
          (this.target = d),
          (this.currentTarget = null));
        for (var f in e)
          e.hasOwnProperty(f) && ((t = e[f]), (this[f] = t ? t(o) : o[f]));
        return (
          (this.isDefaultPrevented = (
            o.defaultPrevented != null
              ? o.defaultPrevented
              : o.returnValue === !1
          )
            ? Ld
            : _r),
          (this.isPropagationStopped = _r),
          this
        );
      }
      return (
        de(a.prototype, {
          preventDefault: function () {
            this.defaultPrevented = !0;
            var t = this.nativeEvent;
            t &&
              (t.preventDefault
                ? t.preventDefault()
                : typeof t.returnValue != "unknown" && (t.returnValue = !1),
              (this.isDefaultPrevented = Ld));
          },
          stopPropagation: function () {
            var t = this.nativeEvent;
            t &&
              (t.stopPropagation
                ? t.stopPropagation()
                : typeof t.cancelBubble != "unknown" && (t.cancelBubble = !0),
              (this.isPropagationStopped = Ld));
          },
          persist: function () {},
          isPersistent: Ld,
        }),
        a
      );
    }
    var yl = {
        eventPhase: 0,
        bubbles: 0,
        cancelable: 0,
        timeStamp: function (e) {
          return e.timeStamp || Date.now();
        },
        defaultPrevented: 0,
        isTrusted: 0,
      },
      ys = Ve(yl),
      wo = de({}, yl, { view: 0, detail: 0 }),
      hx = Ve(wo),
      Sf,
      vf,
      Uu,
      Ss = de({}, wo, {
        screenX: 0,
        screenY: 0,
        clientX: 0,
        clientY: 0,
        pageX: 0,
        pageY: 0,
        ctrlKey: 0,
        shiftKey: 0,
        altKey: 0,
        metaKey: 0,
        getModifierState: $n,
        button: 0,
        buttons: 0,
        relatedTarget: function (e) {
          return e.relatedTarget === void 0
            ? e.fromElement === e.srcElement
              ? e.toElement
              : e.fromElement
            : e.relatedTarget;
        },
        movementX: function (e) {
          return "movementX" in e
            ? e.movementX
            : (e !== Uu &&
                (Uu && e.type === "mousemove"
                  ? ((Sf = e.screenX - Uu.screenX),
                    (vf = e.screenY - Uu.screenY))
                  : (vf = Sf = 0),
                (Uu = e)),
              Sf);
        },
        movementY: function (e) {
          return "movementY" in e ? e.movementY : vf;
        },
      }),
      Gr = Ve(Ss),
      Lx = de({}, Ss, { dataTransfer: 0 }),
      gx = Ve(Lx),
      xx = de({}, wo, { relatedTarget: 0 }),
      If = Ve(xx),
      Cx = de({}, yl, { animationName: 0, elapsedTime: 0, pseudoElement: 0 }),
      yx = Ve(Cx),
      Sx = de({}, yl, {
        clipboardData: function (e) {
          return "clipboardData" in e ? e.clipboardData : window.clipboardData;
        },
      }),
      vx = Ve(Sx),
      Ix = de({}, yl, { data: 0 }),
      Vr = Ve(Ix),
      bx = {
        Esc: "Escape",
        Spacebar: " ",
        Left: "ArrowLeft",
        Up: "ArrowUp",
        Right: "ArrowRight",
        Down: "ArrowDown",
        Del: "Delete",
        Win: "OS",
        Menu: "ContextMenu",
        Apps: "ContextMenu",
        Scroll: "ScrollLock",
        MozPrintableKey: "Unidentified",
      },
      Ax = {
        8: "Backspace",
        9: "Tab",
        12: "Clear",
        13: "Enter",
        16: "Shift",
        17: "Control",
        18: "Alt",
        19: "Pause",
        20: "CapsLock",
        27: "Escape",
        32: " ",
        33: "PageUp",
        34: "PageDown",
        35: "End",
        36: "Home",
        37: "ArrowLeft",
        38: "ArrowUp",
        39: "ArrowRight",
        40: "ArrowDown",
        45: "Insert",
        46: "Delete",
        112: "F1",
        113: "F2",
        114: "F3",
        115: "F4",
        116: "F5",
        117: "F6",
        118: "F7",
        119: "F8",
        120: "F9",
        121: "F10",
        122: "F11",
        123: "F12",
        144: "NumLock",
        145: "ScrollLock",
        224: "Meta",
      },
      kx = {
        Alt: "altKey",
        Control: "ctrlKey",
        Meta: "metaKey",
        Shift: "shiftKey",
      };
    function Mx(e) {
      var a = this.nativeEvent;
      return a.getModifierState
        ? a.getModifierState(e)
        : (e = kx[e])
          ? !!a[e]
          : !1;
    }
    function $n() {
      return Mx;
    }
    var wx = de({}, wo, {
        key: function (e) {
          if (e.key) {
            var a = bx[e.key] || e.key;
            if (a !== "Unidentified") return a;
          }
          return e.type === "keypress"
            ? ((e = zd(e)), e === 13 ? "Enter" : String.fromCharCode(e))
            : e.type === "keydown" || e.type === "keyup"
              ? Ax[e.keyCode] || "Unidentified"
              : "";
        },
        code: 0,
        location: 0,
        ctrlKey: 0,
        shiftKey: 0,
        altKey: 0,
        metaKey: 0,
        repeat: 0,
        locale: 0,
        getModifierState: $n,
        charCode: function (e) {
          return e.type === "keypress" ? zd(e) : 0;
        },
        keyCode: function (e) {
          return e.type === "keydown" || e.type === "keyup" ? e.keyCode : 0;
        },
        which: function (e) {
          return e.type === "keypress"
            ? zd(e)
            : e.type === "keydown" || e.type === "keyup"
              ? e.keyCode
              : 0;
        },
      }),
      Dx = Ve(wx),
      Tx = de({}, Ss, {
        pointerId: 0,
        width: 0,
        height: 0,
        pressure: 0,
        tangentialPressure: 0,
        tiltX: 0,
        tiltY: 0,
        twist: 0,
        pointerType: 0,
        isPrimary: 0,
      }),
      Xr = Ve(Tx),
      Bx = de({}, wo, {
        touches: 0,
        targetTouches: 0,
        changedTouches: 0,
        altKey: 0,
        metaKey: 0,
        ctrlKey: 0,
        shiftKey: 0,
        getModifierState: $n,
      }),
      zx = Ve(Bx),
      Rx = de({}, yl, { propertyName: 0, elapsedTime: 0, pseudoElement: 0 }),
      Ox = Ve(Rx),
      Ex = de({}, Ss, {
        deltaX: function (e) {
          return "deltaX" in e
            ? e.deltaX
            : "wheelDeltaX" in e
              ? -e.wheelDeltaX
              : 0;
        },
        deltaY: function (e) {
          return "deltaY" in e
            ? e.deltaY
            : "wheelDeltaY" in e
              ? -e.wheelDeltaY
              : "wheelDelta" in e
                ? -e.wheelDelta
                : 0;
        },
        deltaZ: 0,
        deltaMode: 0,
      }),
      qx = Ve(Ex),
      Nx = de({}, yl, { newState: 0, oldState: 0 }),
      Ux = Ve(Nx),
      Hx = [9, 13, 27, 32],
      ei = lt && "CompositionEvent" in window,
      Qu = null;
    lt && "documentMode" in document && (Qu = document.documentMode);
    var Fx = lt && "TextEvent" in window && !Qu,
      wm = lt && (!ei || (Qu && 8 < Qu && 11 >= Qu)),
      Yr = " ",
      jr = !1;
    function Dm(e, a) {
      switch (e) {
        case "keyup":
          return Hx.indexOf(a.keyCode) !== -1;
        case "keydown":
          return a.keyCode !== 229;
        case "keypress":
        case "mousedown":
        case "focusout":
          return !0;
        default:
          return !1;
      }
    }
    function Tm(e) {
      return (
        (e = e.detail),
        typeof e == "object" && "data" in e ? e.data : null
      );
    }
    var Xl = !1;
    function Px(e, a) {
      switch (e) {
        case "compositionend":
          return Tm(a);
        case "keypress":
          return a.which !== 32 ? null : ((jr = !0), Yr);
        case "textInput":
          return ((e = a.data), e === Yr && jr ? null : e);
        default:
          return null;
      }
    }
    function _x(e, a) {
      if (Xl)
        return e === "compositionend" || (!ei && Dm(e, a))
          ? ((e = Mm()), (Bd = Wn = vt = null), (Xl = !1), e)
          : null;
      switch (e) {
        case "paste":
          return null;
        case "keypress":
          if (
            !(a.ctrlKey || a.altKey || a.metaKey) ||
            (a.ctrlKey && a.altKey)
          ) {
            if (a.char && 1 < a.char.length) return a.char;
            if (a.which) return String.fromCharCode(a.which);
          }
          return null;
        case "compositionend":
          return wm && a.locale !== "ko" ? null : a.data;
        default:
          return null;
      }
    }
    var Gx = {
      color: !0,
      date: !0,
      datetime: !0,
      "datetime-local": !0,
      email: !0,
      month: !0,
      number: !0,
      password: !0,
      range: !0,
      search: !0,
      tel: !0,
      text: !0,
      time: !0,
      url: !0,
      week: !0,
    };
    function Zr(e) {
      var a = e && e.nodeName && e.nodeName.toLowerCase();
      return a === "input" ? !!Gx[e.type] : a === "textarea";
    }
    function Bm(e, a, t, l) {
      (Vl ? (au ? au.push(l) : (au = [l])) : (Vl = l),
        (a = cs(a, "onChange")),
        0 < a.length &&
          ((t = new ys("onChange", "change", null, t, l)),
          e.push({ event: t, listeners: a })));
    }
    var Ku = null,
      ro = null;
    function Vx(e) {
      kh(e, 0);
    }
    function vs(e) {
      var a = Yu(e);
      if (Sm(a)) return e;
    }
    function Qr(e, a) {
      if (e === "change") return a;
    }
    var zm = !1;
    lt &&
      (lt
        ? ((xd = "oninput" in document),
          xd ||
            ((bf = document.createElement("div")),
            bf.setAttribute("oninput", "return;"),
            (xd = typeof bf.oninput == "function")),
          (gd = xd))
        : (gd = !1),
      (zm = gd && (!document.documentMode || 9 < document.documentMode)));
    var gd, xd, bf;
    function Kr() {
      Ku && (Ku.detachEvent("onpropertychange", Rm), (ro = Ku = null));
    }
    function Rm(e) {
      if (e.propertyName === "value" && vs(ro)) {
        var a = [];
        (Bm(a, ro, e, Jn(e)), km(Vx, a));
      }
    }
    function Xx(e, a, t) {
      e === "focusin"
        ? (Kr(), (Ku = a), (ro = t), Ku.attachEvent("onpropertychange", Rm))
        : e === "focusout" && Kr();
    }
    function Yx(e) {
      if (e === "selectionchange" || e === "keyup" || e === "keydown")
        return vs(ro);
    }
    function jx(e, a) {
      if (e === "click") return vs(a);
    }
    function Zx(e, a) {
      if (e === "input" || e === "change") return vs(a);
    }
    function Qx(e, a) {
      return (e === a && (e !== 0 || 1 / e === 1 / a)) || (e !== e && a !== a);
    }
    var aa = typeof Object.is == "function" ? Object.is : Qx;
    function co(e, a) {
      if (aa(e, a)) return !0;
      if (
        typeof e != "object" ||
        e === null ||
        typeof a != "object" ||
        a === null
      )
        return !1;
      var t = Object.keys(e),
        l = Object.keys(a);
      if (t.length !== l.length) return !1;
      for (l = 0; l < t.length; l++) {
        var u = t[l];
        if (!an.call(a, u) || !aa(e[u], a[u])) return !1;
      }
      return !0;
    }
    function Jr(e) {
      for (; e && e.firstChild; ) e = e.firstChild;
      return e;
    }
    function Wr(e, a) {
      var t = Jr(e);
      e = 0;
      for (var l; t; ) {
        if (t.nodeType === 3) {
          if (((l = e + t.textContent.length), e <= a && l >= a))
            return { node: t, offset: a - e };
          e = l;
        }
        e: {
          for (; t; ) {
            if (t.nextSibling) {
              t = t.nextSibling;
              break e;
            }
            t = t.parentNode;
          }
          t = void 0;
        }
        t = Jr(t);
      }
    }
    function Om(e, a) {
      return e && a
        ? e === a
          ? !0
          : e && e.nodeType === 3
            ? !1
            : a && a.nodeType === 3
              ? Om(e, a.parentNode)
              : "contains" in e
                ? e.contains(a)
                : e.compareDocumentPosition
                  ? !!(e.compareDocumentPosition(a) & 16)
                  : !1
        : !1;
    }
    function Em(e) {
      e =
        e != null &&
        e.ownerDocument != null &&
        e.ownerDocument.defaultView != null
          ? e.ownerDocument.defaultView
          : window;
      for (var a = Zd(e.document); a instanceof e.HTMLIFrameElement; ) {
        try {
          var t = typeof a.contentWindow.location.href == "string";
        } catch {
          t = !1;
        }
        if (t) e = a.contentWindow;
        else break;
        a = Zd(e.document);
      }
      return a;
    }
    function ai(e) {
      var a = e && e.nodeName && e.nodeName.toLowerCase();
      return (
        a &&
        ((a === "input" &&
          (e.type === "text" ||
            e.type === "search" ||
            e.type === "tel" ||
            e.type === "url" ||
            e.type === "password")) ||
          a === "textarea" ||
          e.contentEditable === "true")
      );
    }
    var Kx = lt && "documentMode" in document && 11 >= document.documentMode,
      Yl = null,
      fn = null,
      Ju = null,
      nn = !1;
    function $r(e, a, t) {
      var l =
        t.window === t ? t.document : t.nodeType === 9 ? t : t.ownerDocument;
      nn ||
        Yl == null ||
        Yl !== Zd(l) ||
        ((l = Yl),
        "selectionStart" in l && ai(l)
          ? (l = { start: l.selectionStart, end: l.selectionEnd })
          : ((l = (
              (l.ownerDocument && l.ownerDocument.defaultView) ||
              window
            ).getSelection()),
            (l = {
              anchorNode: l.anchorNode,
              anchorOffset: l.anchorOffset,
              focusNode: l.focusNode,
              focusOffset: l.focusOffset,
            })),
        (Ju && co(Ju, l)) ||
          ((Ju = l),
          (l = cs(fn, "onSelect")),
          0 < l.length &&
            ((a = new ys("onSelect", "select", null, a, t)),
            e.push({ event: a, listeners: l }),
            (a.target = Yl))));
    }
    function ul(e, a) {
      var t = {};
      return (
        (t[e.toLowerCase()] = a.toLowerCase()),
        (t["Webkit" + e] = "webkit" + a),
        (t["Moz" + e] = "moz" + a),
        t
      );
    }
    var jl = {
        animationend: ul("Animation", "AnimationEnd"),
        animationiteration: ul("Animation", "AnimationIteration"),
        animationstart: ul("Animation", "AnimationStart"),
        transitionrun: ul("Transition", "TransitionRun"),
        transitionstart: ul("Transition", "TransitionStart"),
        transitioncancel: ul("Transition", "TransitionCancel"),
        transitionend: ul("Transition", "TransitionEnd"),
      },
      Af = {},
      qm = {};
    lt &&
      ((qm = document.createElement("div").style),
      "AnimationEvent" in window ||
        (delete jl.animationend.animation,
        delete jl.animationiteration.animation,
        delete jl.animationstart.animation),
      "TransitionEvent" in window || delete jl.transitionend.transition);
    function Sl(e) {
      if (Af[e]) return Af[e];
      if (!jl[e]) return e;
      var a = jl[e],
        t;
      for (t in a) if (a.hasOwnProperty(t) && t in qm) return (Af[e] = a[t]);
      return e;
    }
    var Nm = Sl("animationend"),
      Um = Sl("animationiteration"),
      Hm = Sl("animationstart"),
      Jx = Sl("transitionrun"),
      Wx = Sl("transitionstart"),
      $x = Sl("transitioncancel"),
      Fm = Sl("transitionend"),
      Pm = new Map(),
      rn =
        "abort auxClick beforeToggle cancel canPlay canPlayThrough click close contextMenu copy cut drag dragEnd dragEnter dragExit dragLeave dragOver dragStart drop durationChange emptied encrypted ended error gotPointerCapture input invalid keyDown keyPress keyUp load loadedData loadedMetadata loadStart lostPointerCapture mouseDown mouseMove mouseOut mouseOver mouseUp paste pause play playing pointerCancel pointerDown pointerMove pointerOut pointerOver pointerUp progress rateChange reset resize seeked seeking stalled submit suspend timeUpdate touchCancel touchEnd touchStart volumeChange scroll toggle touchMove waiting wheel".split(
          " ",
        );
    rn.push("scrollEnd");
    function Ia(e, a) {
      (Pm.set(e, a), Cl(a, [e]));
    }
    var Qd =
        typeof reportError == "function"
          ? reportError
          : function (e) {
              if (
                typeof window == "object" &&
                typeof window.ErrorEvent == "function"
              ) {
                var a = new window.ErrorEvent("error", {
                  bubbles: !0,
                  cancelable: !0,
                  message:
                    typeof e == "object" &&
                    e !== null &&
                    typeof e.message == "string"
                      ? String(e.message)
                      : String(e),
                  error: e,
                });
                if (!window.dispatchEvent(a)) return;
              } else if (
                typeof process == "object" &&
                typeof process.emit == "function"
              ) {
                process.emit("uncaughtException", e);
                return;
              }
              console.error(e);
            },
      na = [],
      Zl = 0,
      ti = 0;
    function Is() {
      for (var e = Zl, a = (ti = Zl = 0); a < e; ) {
        var t = na[a];
        na[a++] = null;
        var l = na[a];
        na[a++] = null;
        var u = na[a];
        na[a++] = null;
        var o = na[a];
        if (((na[a++] = null), l !== null && u !== null)) {
          var d = l.pending;
          (d === null ? (u.next = u) : ((u.next = d.next), (d.next = u)),
            (l.pending = u));
        }
        o !== 0 && _m(t, u, o);
      }
    }
    function bs(e, a, t, l) {
      ((na[Zl++] = e),
        (na[Zl++] = a),
        (na[Zl++] = t),
        (na[Zl++] = l),
        (ti |= l),
        (e.lanes |= l),
        (e = e.alternate),
        e !== null && (e.lanes |= l));
    }
    function li(e, a, t, l) {
      return (bs(e, a, t, l), Kd(e));
    }
    function vl(e, a) {
      return (bs(e, null, null, a), Kd(e));
    }
    function _m(e, a, t) {
      e.lanes |= t;
      var l = e.alternate;
      l !== null && (l.lanes |= t);
      for (var u = !1, o = e.return; o !== null; )
        ((o.childLanes |= t),
          (l = o.alternate),
          l !== null && (l.childLanes |= t),
          o.tag === 22 &&
            ((e = o.stateNode), e === null || e._visibility & 1 || (u = !0)),
          (e = o),
          (o = o.return));
      return e.tag === 3
        ? ((o = e.stateNode),
          u &&
            a !== null &&
            ((u = 31 - $e(t)),
            (e = o.hiddenUpdates),
            (l = e[u]),
            l === null ? (e[u] = [a]) : l.push(a),
            (a.lane = t | 536870912)),
          o)
        : null;
    }
    function Kd(e) {
      if (50 < so) throw ((so = 0), (zn = null), Error(v(185)));
      for (var a = e.return; a !== null; ) ((e = a), (a = e.return));
      return e.tag === 3 ? e.stateNode : null;
    }
    var Ql = {};
    function eC(e, a, t, l) {
      ((this.tag = e),
        (this.key = t),
        (this.sibling =
          this.child =
          this.return =
          this.stateNode =
          this.type =
          this.elementType =
            null),
        (this.index = 0),
        (this.refCleanup = this.ref = null),
        (this.pendingProps = a),
        (this.dependencies =
          this.memoizedState =
          this.updateQueue =
          this.memoizedProps =
            null),
        (this.mode = l),
        (this.subtreeFlags = this.flags = 0),
        (this.deletions = null),
        (this.childLanes = this.lanes = 0),
        (this.alternate = null));
    }
    function Qe(e, a, t, l) {
      return new eC(e, a, t, l);
    }
    function ui(e) {
      return ((e = e.prototype), !(!e || !e.isReactComponent));
    }
    function et(e, a) {
      var t = e.alternate;
      return (
        t === null
          ? ((t = Qe(e.tag, a, e.key, e.mode)),
            (t.elementType = e.elementType),
            (t.type = e.type),
            (t.stateNode = e.stateNode),
            (t.alternate = e),
            (e.alternate = t))
          : ((t.pendingProps = a),
            (t.type = e.type),
            (t.flags = 0),
            (t.subtreeFlags = 0),
            (t.deletions = null)),
        (t.flags = e.flags & 65011712),
        (t.childLanes = e.childLanes),
        (t.lanes = e.lanes),
        (t.child = e.child),
        (t.memoizedProps = e.memoizedProps),
        (t.memoizedState = e.memoizedState),
        (t.updateQueue = e.updateQueue),
        (a = e.dependencies),
        (t.dependencies =
          a === null ? null : { lanes: a.lanes, firstContext: a.firstContext }),
        (t.sibling = e.sibling),
        (t.index = e.index),
        (t.ref = e.ref),
        (t.refCleanup = e.refCleanup),
        t
      );
    }
    function Gm(e, a) {
      e.flags &= 65011714;
      var t = e.alternate;
      return (
        t === null
          ? ((e.childLanes = 0),
            (e.lanes = a),
            (e.child = null),
            (e.subtreeFlags = 0),
            (e.memoizedProps = null),
            (e.memoizedState = null),
            (e.updateQueue = null),
            (e.dependencies = null),
            (e.stateNode = null))
          : ((e.childLanes = t.childLanes),
            (e.lanes = t.lanes),
            (e.child = t.child),
            (e.subtreeFlags = 0),
            (e.deletions = null),
            (e.memoizedProps = t.memoizedProps),
            (e.memoizedState = t.memoizedState),
            (e.updateQueue = t.updateQueue),
            (e.type = t.type),
            (a = t.dependencies),
            (e.dependencies =
              a === null
                ? null
                : { lanes: a.lanes, firstContext: a.firstContext })),
        e
      );
    }
    function Rd(e, a, t, l, u, o) {
      var d = 0;
      if (((l = e), typeof e == "function")) ui(e) && (d = 1);
      else if (typeof e == "string")
        d = ly(e, t, Oa.current)
          ? 26
          : e === "html" || e === "head" || e === "body"
            ? 27
            : 5;
      else
        e: switch (e) {
          case Jf:
            return (
              (e = Qe(31, t, a, u)),
              (e.elementType = Jf),
              (e.lanes = o),
              e
            );
          case Pl:
            return il(t.children, u, o, a);
          case fm:
            ((d = 8), (u |= 24));
            break;
          case Zf:
            return (
              (e = Qe(12, t, a, u | 2)),
              (e.elementType = Zf),
              (e.lanes = o),
              e
            );
          case Qf:
            return (
              (e = Qe(13, t, a, u)),
              (e.elementType = Qf),
              (e.lanes = o),
              e
            );
          case Kf:
            return (
              (e = Qe(19, t, a, u)),
              (e.elementType = Kf),
              (e.lanes = o),
              e
            );
          default:
            if (typeof e == "object" && e !== null)
              switch (e.$$typeof) {
                case Ja:
                  d = 10;
                  break e;
                case nm:
                  d = 9;
                  break e;
                case Vn:
                  d = 11;
                  break e;
                case Xn:
                  d = 14;
                  break e;
                case ht:
                  ((d = 16), (l = null));
                  break e;
              }
            ((d = 29),
              (t = Error(v(130, e === null ? "null" : typeof e, ""))),
              (l = null));
        }
      return (
        (a = Qe(d, t, a, u)),
        (a.elementType = e),
        (a.type = l),
        (a.lanes = o),
        a
      );
    }
    function il(e, a, t, l) {
      return ((e = Qe(7, e, l, a)), (e.lanes = t), e);
    }
    function kf(e, a, t) {
      return ((e = Qe(6, e, null, a)), (e.lanes = t), e);
    }
    function Vm(e) {
      var a = Qe(18, null, null, 0);
      return ((a.stateNode = e), a);
    }
    function Mf(e, a, t) {
      return (
        (a = Qe(4, e.children !== null ? e.children : [], e.key, a)),
        (a.lanes = t),
        (a.stateNode = {
          containerInfo: e.containerInfo,
          pendingChildren: null,
          implementation: e.implementation,
        }),
        a
      );
    }
    var ec = new WeakMap();
    function pa(e, a) {
      if (typeof e == "object" && e !== null) {
        var t = ec.get(e);
        return t !== void 0
          ? t
          : ((a = { value: e, source: a, stack: Er(a) }), ec.set(e, a), a);
      }
      return { value: e, source: a, stack: Er(a) };
    }
    var Kl = [],
      Jl = 0,
      Jd = null,
      mo = 0,
      ra = [],
      ca = 0,
      Et = null,
      Ba = 1,
      za = "";
    function Qa(e, a) {
      ((Kl[Jl++] = mo), (Kl[Jl++] = Jd), (Jd = e), (mo = a));
    }
    function Xm(e, a, t) {
      ((ra[ca++] = Ba), (ra[ca++] = za), (ra[ca++] = Et), (Et = e));
      var l = Ba;
      e = za;
      var u = 32 - $e(l) - 1;
      ((l &= ~(1 << u)), (t += 1));
      var o = 32 - $e(a) + u;
      if (30 < o) {
        var d = u - (u % 5);
        ((o = (l & ((1 << d) - 1)).toString(32)),
          (l >>= d),
          (u -= d),
          (Ba = (1 << (32 - $e(a) + u)) | (t << u) | l),
          (za = o + e));
      } else ((Ba = (1 << o) | (t << u) | l), (za = e));
    }
    function oi(e) {
      e.return !== null && (Qa(e, 1), Xm(e, 1, 0));
    }
    function di(e) {
      for (; e === Jd; )
        ((Jd = Kl[--Jl]), (Kl[Jl] = null), (mo = Kl[--Jl]), (Kl[Jl] = null));
      for (; e === Et; )
        ((Et = ra[--ca]),
          (ra[ca] = null),
          (za = ra[--ca]),
          (ra[ca] = null),
          (Ba = ra[--ca]),
          (ra[ca] = null));
    }
    function Ym(e, a) {
      ((ra[ca++] = Ba),
        (ra[ca++] = za),
        (ra[ca++] = Et),
        (Ba = a.id),
        (za = a.overflow),
        (Et = e));
    }
    var ke = null,
      oe = null,
      Y = !1,
      Mt = null,
      ha = !1,
      cn = Error(v(519));
    function qt(e) {
      var a = Error(
        v(
          418,
          1 < arguments.length && arguments[1] !== void 0 && arguments[1]
            ? "text"
            : "HTML",
          "",
        ),
      );
      throw (po(pa(a, e)), cn);
    }
    function ac(e) {
      var a = e.stateNode,
        t = e.type,
        l = e.memoizedProps;
      switch (((a[Ae] = e), (a[Ge] = l), t)) {
        case "dialog":
          (_("cancel", a), _("close", a));
          break;
        case "iframe":
        case "object":
        case "embed":
          _("load", a);
          break;
        case "video":
        case "audio":
          for (t = 0; t < xo.length; t++) _(xo[t], a);
          break;
        case "source":
          _("error", a);
          break;
        case "img":
        case "image":
        case "link":
          (_("error", a), _("load", a));
          break;
        case "details":
          _("toggle", a);
          break;
        case "input":
          (_("invalid", a),
            vm(
              a,
              l.value,
              l.defaultValue,
              l.checked,
              l.defaultChecked,
              l.type,
              l.name,
              !0,
            ));
          break;
        case "select":
          _("invalid", a);
          break;
        case "textarea":
          (_("invalid", a), bm(a, l.value, l.defaultValue, l.children));
      }
      ((t = l.children),
        (typeof t != "string" &&
          typeof t != "number" &&
          typeof t != "bigint") ||
        a.textContent === "" + t ||
        l.suppressHydrationWarning === !0 ||
        wh(a.textContent, t)
          ? (l.popover != null && (_("beforetoggle", a), _("toggle", a)),
            l.onScroll != null && _("scroll", a),
            l.onScrollEnd != null && _("scrollend", a),
            l.onClick != null && (a.onclick = Wa),
            (a = !0))
          : (a = !1),
        a || qt(e, !0));
    }
    function tc(e) {
      for (ke = e.return; ke; )
        switch (ke.tag) {
          case 5:
          case 31:
          case 13:
            ha = !1;
            return;
          case 27:
          case 3:
            ha = !0;
            return;
          default:
            ke = ke.return;
        }
    }
    function Nl(e) {
      if (e !== ke) return !1;
      if (!Y) return (tc(e), (Y = !0), !1);
      var a = e.tag,
        t;
      if (
        ((t = a !== 3 && a !== 27) &&
          ((t = a === 5) &&
            ((t = e.type),
            (t =
              !(t !== "form" && t !== "button") ||
              Nn(e.type, e.memoizedProps))),
          (t = !t)),
        t && oe && qt(e),
        tc(e),
        a === 13)
      ) {
        if (((e = e.memoizedState), (e = e !== null ? e.dehydrated : null), !e))
          throw Error(v(317));
        oe = Vc(e);
      } else if (a === 31) {
        if (((e = e.memoizedState), (e = e !== null ? e.dehydrated : null), !e))
          throw Error(v(317));
        oe = Vc(e);
      } else
        a === 27
          ? ((a = oe),
            Ft(e.type) ? ((e = Pn), (Pn = null), (oe = e)) : (oe = a))
          : (oe = ke ? ga(e.stateNode.nextSibling) : null);
      return !0;
    }
    function pl() {
      ((oe = ke = null), (Y = !1));
    }
    function wf() {
      var e = Mt;
      return (
        e !== null &&
          (Pe === null ? (Pe = e) : Pe.push.apply(Pe, e), (Mt = null)),
        e
      );
    }
    function po(e) {
      Mt === null ? (Mt = [e]) : Mt.push(e);
    }
    var mn = Ea(null),
      Il = null,
      $a = null;
    function gt(e, a, t) {
      (te(mn, a._currentValue), (a._currentValue = t));
    }
    function at(e) {
      ((e._currentValue = mn.current), Ie(mn));
    }
    function pn(e, a, t) {
      for (; e !== null; ) {
        var l = e.alternate;
        if (
          ((e.childLanes & a) !== a
            ? ((e.childLanes |= a), l !== null && (l.childLanes |= a))
            : l !== null && (l.childLanes & a) !== a && (l.childLanes |= a),
          e === t)
        )
          break;
        e = e.return;
      }
    }
    function hn(e, a, t, l) {
      var u = e.child;
      for (u !== null && (u.return = e); u !== null; ) {
        var o = u.dependencies;
        if (o !== null) {
          var d = u.child;
          o = o.firstContext;
          e: for (; o !== null; ) {
            var f = o;
            o = u;
            for (var n = 0; n < a.length; n++)
              if (f.context === a[n]) {
                ((o.lanes |= t),
                  (f = o.alternate),
                  f !== null && (f.lanes |= t),
                  pn(o.return, t, e),
                  l || (d = null));
                break e;
              }
            o = f.next;
          }
        } else if (u.tag === 18) {
          if (((d = u.return), d === null)) throw Error(v(341));
          ((d.lanes |= t),
            (o = d.alternate),
            o !== null && (o.lanes |= t),
            pn(d, t, e),
            (d = null));
        } else d = u.child;
        if (d !== null) d.return = u;
        else
          for (d = u; d !== null; ) {
            if (d === e) {
              d = null;
              break;
            }
            if (((u = d.sibling), u !== null)) {
              ((u.return = d.return), (d = u));
              break;
            }
            d = d.return;
          }
        u = d;
      }
    }
    function Cu(e, a, t, l) {
      e = null;
      for (var u = a, o = !1; u !== null; ) {
        if (!o) {
          if ((u.flags & 524288) !== 0) o = !0;
          else if ((u.flags & 262144) !== 0) break;
        }
        if (u.tag === 10) {
          var d = u.alternate;
          if (d === null) throw Error(v(387));
          if (((d = d.memoizedProps), d !== null)) {
            var f = u.type;
            aa(u.pendingProps.value, d.value) ||
              (e !== null ? e.push(f) : (e = [f]));
          }
        } else if (u === Vd.current) {
          if (((d = u.alternate), d === null)) throw Error(v(387));
          d.memoizedState.memoizedState !== u.memoizedState.memoizedState &&
            (e !== null ? e.push(yo) : (e = [yo]));
        }
        u = u.return;
      }
      (e !== null && hn(a, e, t, l), (a.flags |= 262144));
    }
    function Wd(e) {
      for (e = e.firstContext; e !== null; ) {
        if (!aa(e.context._currentValue, e.memoizedValue)) return !0;
        e = e.next;
      }
      return !1;
    }
    function hl(e) {
      ((Il = e),
        ($a = null),
        (e = e.dependencies),
        e !== null && (e.firstContext = null));
    }
    function Me(e) {
      return jm(Il, e);
    }
    function Cd(e, a) {
      return (Il === null && hl(e), jm(e, a));
    }
    function jm(e, a) {
      var t = a._currentValue;
      if (((a = { context: a, memoizedValue: t, next: null }), $a === null)) {
        if (e === null) throw Error(v(308));
        (($a = a),
          (e.dependencies = { lanes: 0, firstContext: a }),
          (e.flags |= 524288));
      } else $a = $a.next = a;
      return t;
    }
    var aC =
        typeof AbortController < "u"
          ? AbortController
          : function () {
              var e = [],
                a = (this.signal = {
                  aborted: !1,
                  addEventListener: function (t, l) {
                    e.push(l);
                  },
                });
              this.abort = function () {
                ((a.aborted = !0),
                  e.forEach(function (t) {
                    return t();
                  }));
              };
            },
      tC = ye.unstable_scheduleCallback,
      lC = ye.unstable_NormalPriority,
      ge = {
        $$typeof: Ja,
        Consumer: null,
        Provider: null,
        _currentValue: null,
        _currentValue2: null,
        _threadCount: 0,
      };
    function si() {
      return { controller: new aC(), data: new Map(), refCount: 0 };
    }
    function Do(e) {
      (e.refCount--,
        e.refCount === 0 &&
          tC(lC, function () {
            e.controller.abort();
          }));
    }
    var Wu = null,
      Ln = 0,
      nu = 0,
      tu = null;
    function uC(e, a) {
      if (Wu === null) {
        var t = (Wu = []);
        ((Ln = 0),
          (nu = zi()),
          (tu = {
            status: "pending",
            value: void 0,
            then: function (l) {
              t.push(l);
            },
          }));
      }
      return (Ln++, a.then(lc, lc), a);
    }
    function lc() {
      if (--Ln === 0 && Wu !== null) {
        tu !== null && (tu.status = "fulfilled");
        var e = Wu;
        ((Wu = null), (nu = 0), (tu = null));
        for (var a = 0; a < e.length; a++) (0, e[a])();
      }
    }
    function oC(e, a) {
      var t = [],
        l = {
          status: "pending",
          value: null,
          reason: null,
          then: function (u) {
            t.push(u);
          },
        };
      return (
        e.then(
          function () {
            ((l.status = "fulfilled"), (l.value = a));
            for (var u = 0; u < t.length; u++) (0, t[u])(a);
          },
          function (u) {
            for (l.status = "rejected", l.reason = u, u = 0; u < t.length; u++)
              (0, t[u])(void 0);
          },
        ),
        l
      );
    }
    var uc = R.S;
    R.S = function (e, a) {
      ((sh = Je()),
        typeof a == "object" &&
          a !== null &&
          typeof a.then == "function" &&
          uC(e, a),
        uc !== null && uc(e, a));
    };
    var rl = Ea(null);
    function fi() {
      var e = rl.current;
      return e !== null ? e : ae.pooledCache;
    }
    function Od(e, a) {
      a === null ? te(rl, rl.current) : te(rl, a.pool);
    }
    function Zm() {
      var e = fi();
      return e === null ? null : { parent: ge._currentValue, pool: e };
    }
    var yu = Error(v(460)),
      ni = Error(v(474)),
      As = Error(v(542)),
      $d = { then: function () {} };
    function oc(e) {
      return ((e = e.status), e === "fulfilled" || e === "rejected");
    }
    function Qm(e, a, t) {
      switch (
        ((t = e[t]),
        t === void 0 ? e.push(a) : t !== a && (a.then(Wa, Wa), (a = t)),
        a.status)
      ) {
        case "fulfilled":
          return a.value;
        case "rejected":
          throw ((e = a.reason), sc(e), e);
        default:
          if (typeof a.status == "string") a.then(Wa, Wa);
          else {
            if (((e = ae), e !== null && 100 < e.shellSuspendCounter))
              throw Error(v(482));
            ((e = a),
              (e.status = "pending"),
              e.then(
                function (l) {
                  if (a.status === "pending") {
                    var u = a;
                    ((u.status = "fulfilled"), (u.value = l));
                  }
                },
                function (l) {
                  if (a.status === "pending") {
                    var u = a;
                    ((u.status = "rejected"), (u.reason = l));
                  }
                },
              ));
          }
          switch (a.status) {
            case "fulfilled":
              return a.value;
            case "rejected":
              throw ((e = a.reason), sc(e), e);
          }
          throw ((cl = a), yu);
      }
    }
    function sl(e) {
      try {
        var a = e._init;
        return a(e._payload);
      } catch (t) {
        throw t !== null && typeof t == "object" && typeof t.then == "function"
          ? ((cl = t), yu)
          : t;
      }
    }
    var cl = null;
    function dc() {
      if (cl === null) throw Error(v(459));
      var e = cl;
      return ((cl = null), e);
    }
    function sc(e) {
      if (e === yu || e === As) throw Error(v(483));
    }
    var lu = null,
      ho = 0;
    function yd(e) {
      var a = ho;
      return ((ho += 1), lu === null && (lu = []), Qm(lu, e, a));
    }
    function Hu(e, a) {
      ((a = a.props.ref), (e.ref = a !== void 0 ? a : null));
    }
    function Sd(e, a) {
      throw a.$$typeof === Xg
        ? Error(v(525))
        : ((e = Object.prototype.toString.call(a)),
          Error(
            v(
              31,
              e === "[object Object]"
                ? "object with keys {" + Object.keys(a).join(", ") + "}"
                : e,
            ),
          ));
    }
    function Km(e) {
      function a(c, r) {
        if (e) {
          var g = c.deletions;
          g === null ? ((c.deletions = [r]), (c.flags |= 16)) : g.push(r);
        }
      }
      function t(c, r) {
        if (!e) return null;
        for (; r !== null; ) (a(c, r), (r = r.sibling));
        return null;
      }
      function l(c) {
        for (var r = new Map(); c !== null; )
          (c.key !== null ? r.set(c.key, c) : r.set(c.index, c),
            (c = c.sibling));
        return r;
      }
      function u(c, r) {
        return ((c = et(c, r)), (c.index = 0), (c.sibling = null), c);
      }
      function o(c, r, g) {
        return (
          (c.index = g),
          e
            ? ((g = c.alternate),
              g !== null
                ? ((g = g.index), g < r ? ((c.flags |= 67108866), r) : g)
                : ((c.flags |= 67108866), r))
            : ((c.flags |= 1048576), r)
        );
      }
      function d(c) {
        return (e && c.alternate === null && (c.flags |= 67108866), c);
      }
      function f(c, r, g, y) {
        return r === null || r.tag !== 6
          ? ((r = kf(g, c.mode, y)), (r.return = c), r)
          : ((r = u(r, g)), (r.return = c), r);
      }
      function n(c, r, g, y) {
        var T = g.type;
        return T === Pl
          ? h(c, r, g.props.children, y, g.key)
          : r !== null &&
              (r.elementType === T ||
                (typeof T == "object" &&
                  T !== null &&
                  T.$$typeof === ht &&
                  sl(T) === r.type))
            ? ((r = u(r, g.props)), Hu(r, g), (r.return = c), r)
            : ((r = Rd(g.type, g.key, g.props, null, c.mode, y)),
              Hu(r, g),
              (r.return = c),
              r);
      }
      function p(c, r, g, y) {
        return r === null ||
          r.tag !== 4 ||
          r.stateNode.containerInfo !== g.containerInfo ||
          r.stateNode.implementation !== g.implementation
          ? ((r = Mf(g, c.mode, y)), (r.return = c), r)
          : ((r = u(r, g.children || [])), (r.return = c), r);
      }
      function h(c, r, g, y, T) {
        return r === null || r.tag !== 7
          ? ((r = il(g, c.mode, y, T)), (r.return = c), r)
          : ((r = u(r, g)), (r.return = c), r);
      }
      function x(c, r, g) {
        if (
          (typeof r == "string" && r !== "") ||
          typeof r == "number" ||
          typeof r == "bigint"
        )
          return ((r = kf("" + r, c.mode, g)), (r.return = c), r);
        if (typeof r == "object" && r !== null) {
          switch (r.$$typeof) {
            case rd:
              return (
                (g = Rd(r.type, r.key, r.props, null, c.mode, g)),
                Hu(g, r),
                (g.return = c),
                g
              );
            case Vu:
              return ((r = Mf(r, c.mode, g)), (r.return = c), r);
            case ht:
              return ((r = sl(r)), x(c, r, g));
          }
          if (Xu(r) || Nu(r))
            return ((r = il(r, c.mode, g, null)), (r.return = c), r);
          if (typeof r.then == "function") return x(c, yd(r), g);
          if (r.$$typeof === Ja) return x(c, Cd(c, r), g);
          Sd(c, r);
        }
        return null;
      }
      function m(c, r, g, y) {
        var T = r !== null ? r.key : null;
        if (
          (typeof g == "string" && g !== "") ||
          typeof g == "number" ||
          typeof g == "bigint"
        )
          return T !== null ? null : f(c, r, "" + g, y);
        if (typeof g == "object" && g !== null) {
          switch (g.$$typeof) {
            case rd:
              return g.key === T ? n(c, r, g, y) : null;
            case Vu:
              return g.key === T ? p(c, r, g, y) : null;
            case ht:
              return ((g = sl(g)), m(c, r, g, y));
          }
          if (Xu(g) || Nu(g)) return T !== null ? null : h(c, r, g, y, null);
          if (typeof g.then == "function") return m(c, r, yd(g), y);
          if (g.$$typeof === Ja) return m(c, r, Cd(c, g), y);
          Sd(c, g);
        }
        return null;
      }
      function L(c, r, g, y, T) {
        if (
          (typeof y == "string" && y !== "") ||
          typeof y == "number" ||
          typeof y == "bigint"
        )
          return ((c = c.get(g) || null), f(r, c, "" + y, T));
        if (typeof y == "object" && y !== null) {
          switch (y.$$typeof) {
            case rd:
              return (
                (c = c.get(y.key === null ? g : y.key) || null),
                n(r, c, y, T)
              );
            case Vu:
              return (
                (c = c.get(y.key === null ? g : y.key) || null),
                p(r, c, y, T)
              );
            case ht:
              return ((y = sl(y)), L(c, r, g, y, T));
          }
          if (Xu(y) || Nu(y))
            return ((c = c.get(g) || null), h(r, c, y, T, null));
          if (typeof y.then == "function") return L(c, r, g, yd(y), T);
          if (y.$$typeof === Ja) return L(c, r, g, Cd(r, y), T);
          Sd(r, y);
        }
        return null;
      }
      function k(c, r, g, y) {
        for (
          var T = null, V = null, D = r, B = (r = 0), U = null;
          D !== null && B < g.length;
          B++
        ) {
          D.index > B ? ((U = D), (D = null)) : (U = D.sibling);
          var P = m(c, D, g[B], y);
          if (P === null) {
            D === null && (D = U);
            break;
          }
          (e && D && P.alternate === null && a(c, D),
            (r = o(P, r, B)),
            V === null ? (T = P) : (V.sibling = P),
            (V = P),
            (D = U));
        }
        if (B === g.length) return (t(c, D), Y && Qa(c, B), T);
        if (D === null) {
          for (; B < g.length; B++)
            ((D = x(c, g[B], y)),
              D !== null &&
                ((r = o(D, r, B)),
                V === null ? (T = D) : (V.sibling = D),
                (V = D)));
          return (Y && Qa(c, B), T);
        }
        for (D = l(D); B < g.length; B++)
          ((U = L(D, c, B, g[B], y)),
            U !== null &&
              (e &&
                U.alternate !== null &&
                D.delete(U.key === null ? B : U.key),
              (r = o(U, r, B)),
              V === null ? (T = U) : (V.sibling = U),
              (V = U)));
        return (
          e &&
            D.forEach(function (wa) {
              return a(c, wa);
            }),
          Y && Qa(c, B),
          T
        );
      }
      function w(c, r, g, y) {
        if (g == null) throw Error(v(151));
        for (
          var T = null, V = null, D = r, B = (r = 0), U = null, P = g.next();
          D !== null && !P.done;
          B++, P = g.next()
        ) {
          D.index > B ? ((U = D), (D = null)) : (U = D.sibling);
          var wa = m(c, D, P.value, y);
          if (wa === null) {
            D === null && (D = U);
            break;
          }
          (e && D && wa.alternate === null && a(c, D),
            (r = o(wa, r, B)),
            V === null ? (T = wa) : (V.sibling = wa),
            (V = wa),
            (D = U));
        }
        if (P.done) return (t(c, D), Y && Qa(c, B), T);
        if (D === null) {
          for (; !P.done; B++, P = g.next())
            ((P = x(c, P.value, y)),
              P !== null &&
                ((r = o(P, r, B)),
                V === null ? (T = P) : (V.sibling = P),
                (V = P)));
          return (Y && Qa(c, B), T);
        }
        for (D = l(D); !P.done; B++, P = g.next())
          ((P = L(D, c, B, P.value, y)),
            P !== null &&
              (e &&
                P.alternate !== null &&
                D.delete(P.key === null ? B : P.key),
              (r = o(P, r, B)),
              V === null ? (T = P) : (V.sibling = P),
              (V = P)));
        return (
          e &&
            D.forEach(function (Gs) {
              return a(c, Gs);
            }),
          Y && Qa(c, B),
          T
        );
      }
      function A(c, r, g, y) {
        if (
          (typeof g == "object" &&
            g !== null &&
            g.type === Pl &&
            g.key === null &&
            (g = g.props.children),
          typeof g == "object" && g !== null)
        ) {
          switch (g.$$typeof) {
            case rd:
              e: {
                for (var T = g.key; r !== null; ) {
                  if (r.key === T) {
                    if (((T = g.type), T === Pl)) {
                      if (r.tag === 7) {
                        (t(c, r.sibling),
                          (y = u(r, g.props.children)),
                          (y.return = c),
                          (c = y));
                        break e;
                      }
                    } else if (
                      r.elementType === T ||
                      (typeof T == "object" &&
                        T !== null &&
                        T.$$typeof === ht &&
                        sl(T) === r.type)
                    ) {
                      (t(c, r.sibling),
                        (y = u(r, g.props)),
                        Hu(y, g),
                        (y.return = c),
                        (c = y));
                      break e;
                    }
                    t(c, r);
                    break;
                  } else a(c, r);
                  r = r.sibling;
                }
                g.type === Pl
                  ? ((y = il(g.props.children, c.mode, y, g.key)),
                    (y.return = c),
                    (c = y))
                  : ((y = Rd(g.type, g.key, g.props, null, c.mode, y)),
                    Hu(y, g),
                    (y.return = c),
                    (c = y));
              }
              return d(c);
            case Vu:
              e: {
                for (T = g.key; r !== null; ) {
                  if (r.key === T)
                    if (
                      r.tag === 4 &&
                      r.stateNode.containerInfo === g.containerInfo &&
                      r.stateNode.implementation === g.implementation
                    ) {
                      (t(c, r.sibling),
                        (y = u(r, g.children || [])),
                        (y.return = c),
                        (c = y));
                      break e;
                    } else {
                      t(c, r);
                      break;
                    }
                  else a(c, r);
                  r = r.sibling;
                }
                ((y = Mf(g, c.mode, y)), (y.return = c), (c = y));
              }
              return d(c);
            case ht:
              return ((g = sl(g)), A(c, r, g, y));
          }
          if (Xu(g)) return k(c, r, g, y);
          if (Nu(g)) {
            if (((T = Nu(g)), typeof T != "function")) throw Error(v(150));
            return ((g = T.call(g)), w(c, r, g, y));
          }
          if (typeof g.then == "function") return A(c, r, yd(g), y);
          if (g.$$typeof === Ja) return A(c, r, Cd(c, g), y);
          Sd(c, g);
        }
        return (typeof g == "string" && g !== "") ||
          typeof g == "number" ||
          typeof g == "bigint"
          ? ((g = "" + g),
            r !== null && r.tag === 6
              ? (t(c, r.sibling), (y = u(r, g)), (y.return = c), (c = y))
              : (t(c, r), (y = kf(g, c.mode, y)), (y.return = c), (c = y)),
            d(c))
          : t(c, r);
      }
      return function (c, r, g, y) {
        try {
          ho = 0;
          var T = A(c, r, g, y);
          return ((lu = null), T);
        } catch (D) {
          if (D === yu || D === As) throw D;
          var V = Qe(29, D, null, c.mode);
          return ((V.lanes = y), (V.return = c), V);
        }
      };
    }
    var Ll = Km(!0),
      Jm = Km(!1),
      Lt = !1;
    function ii(e) {
      e.updateQueue = {
        baseState: e.memoizedState,
        firstBaseUpdate: null,
        lastBaseUpdate: null,
        shared: { pending: null, lanes: 0, hiddenCallbacks: null },
        callbacks: null,
      };
    }
    function gn(e, a) {
      ((e = e.updateQueue),
        a.updateQueue === e &&
          (a.updateQueue = {
            baseState: e.baseState,
            firstBaseUpdate: e.firstBaseUpdate,
            lastBaseUpdate: e.lastBaseUpdate,
            shared: e.shared,
            callbacks: null,
          }));
    }
    function wt(e) {
      return { lane: e, tag: 0, payload: null, callback: null, next: null };
    }
    function Dt(e, a, t) {
      var l = e.updateQueue;
      if (l === null) return null;
      if (((l = l.shared), (Q & 2) !== 0)) {
        var u = l.pending;
        return (
          u === null ? (a.next = a) : ((a.next = u.next), (u.next = a)),
          (l.pending = a),
          (a = Kd(e)),
          _m(e, null, t),
          a
        );
      }
      return (bs(e, l, a, t), Kd(e));
    }
    function $u(e, a, t) {
      if (
        ((a = a.updateQueue),
        a !== null && ((a = a.shared), (t & 4194048) !== 0))
      ) {
        var l = a.lanes;
        ((l &= e.pendingLanes), (t |= l), (a.lanes = t), hm(e, t));
      }
    }
    function Df(e, a) {
      var t = e.updateQueue,
        l = e.alternate;
      if (l !== null && ((l = l.updateQueue), t === l)) {
        var u = null,
          o = null;
        if (((t = t.firstBaseUpdate), t !== null)) {
          do {
            var d = {
              lane: t.lane,
              tag: t.tag,
              payload: t.payload,
              callback: null,
              next: null,
            };
            (o === null ? (u = o = d) : (o = o.next = d), (t = t.next));
          } while (t !== null);
          o === null ? (u = o = a) : (o = o.next = a);
        } else u = o = a;
        ((t = {
          baseState: l.baseState,
          firstBaseUpdate: u,
          lastBaseUpdate: o,
          shared: l.shared,
          callbacks: l.callbacks,
        }),
          (e.updateQueue = t));
        return;
      }
      ((e = t.lastBaseUpdate),
        e === null ? (t.firstBaseUpdate = a) : (e.next = a),
        (t.lastBaseUpdate = a));
    }
    var xn = !1;
    function eo() {
      if (xn) {
        var e = tu;
        if (e !== null) throw e;
      }
    }
    function ao(e, a, t, l) {
      xn = !1;
      var u = e.updateQueue;
      Lt = !1;
      var o = u.firstBaseUpdate,
        d = u.lastBaseUpdate,
        f = u.shared.pending;
      if (f !== null) {
        u.shared.pending = null;
        var n = f,
          p = n.next;
        ((n.next = null), d === null ? (o = p) : (d.next = p), (d = n));
        var h = e.alternate;
        h !== null &&
          ((h = h.updateQueue),
          (f = h.lastBaseUpdate),
          f !== d &&
            (f === null ? (h.firstBaseUpdate = p) : (f.next = p),
            (h.lastBaseUpdate = n)));
      }
      if (o !== null) {
        var x = u.baseState;
        ((d = 0), (h = p = n = null), (f = o));
        do {
          var m = f.lane & -536870913,
            L = m !== f.lane;
          if (L ? (X & m) === m : (l & m) === m) {
            (m !== 0 && m === nu && (xn = !0),
              h !== null &&
                (h = h.next =
                  {
                    lane: 0,
                    tag: f.tag,
                    payload: f.payload,
                    callback: null,
                    next: null,
                  }));
            e: {
              var k = e,
                w = f;
              m = a;
              var A = t;
              switch (w.tag) {
                case 1:
                  if (((k = w.payload), typeof k == "function")) {
                    x = k.call(A, x, m);
                    break e;
                  }
                  x = k;
                  break e;
                case 3:
                  k.flags = (k.flags & -65537) | 128;
                case 0:
                  if (
                    ((k = w.payload),
                    (m = typeof k == "function" ? k.call(A, x, m) : k),
                    m == null)
                  )
                    break e;
                  x = de({}, x, m);
                  break e;
                case 2:
                  Lt = !0;
              }
            }
            ((m = f.callback),
              m !== null &&
                ((e.flags |= 64),
                L && (e.flags |= 8192),
                (L = u.callbacks),
                L === null ? (u.callbacks = [m]) : L.push(m)));
          } else
            ((L = {
              lane: m,
              tag: f.tag,
              payload: f.payload,
              callback: f.callback,
              next: null,
            }),
              h === null ? ((p = h = L), (n = x)) : (h = h.next = L),
              (d |= m));
          if (((f = f.next), f === null)) {
            if (((f = u.shared.pending), f === null)) break;
            ((L = f),
              (f = L.next),
              (L.next = null),
              (u.lastBaseUpdate = L),
              (u.shared.pending = null));
          }
        } while (!0);
        (h === null && (n = x),
          (u.baseState = n),
          (u.firstBaseUpdate = p),
          (u.lastBaseUpdate = h),
          o === null && (u.shared.lanes = 0),
          (Ut |= d),
          (e.lanes = d),
          (e.memoizedState = x));
      }
    }
    function Wm(e, a) {
      if (typeof e != "function") throw Error(v(191, e));
      e.call(a);
    }
    function $m(e, a) {
      var t = e.callbacks;
      if (t !== null)
        for (e.callbacks = null, e = 0; e < t.length; e++) Wm(t[e], a);
    }
    var iu = Ea(null),
      es = Ea(0);
    function fc(e, a) {
      ((e = st), te(es, e), te(iu, a), (st = e | a.baseLanes));
    }
    function Cn() {
      (te(es, st), te(iu, iu.current));
    }
    function ri() {
      ((st = es.current), Ie(iu), Ie(es));
    }
    var ta = Ea(null),
      La = null;
    function xt(e) {
      var a = e.alternate;
      (te(me, me.current & 1),
        te(ta, e),
        La === null &&
          (a === null || iu.current !== null || a.memoizedState !== null) &&
          (La = e));
    }
    function yn(e) {
      (te(me, me.current), te(ta, e), La === null && (La = e));
    }
    function ep(e) {
      e.tag === 22
        ? (te(me, me.current), te(ta, e), La === null && (La = e))
        : Ct(e);
    }
    function Ct() {
      (te(me, me.current), te(ta, ta.current));
    }
    function Ze(e) {
      (Ie(ta), La === e && (La = null), Ie(me));
    }
    var me = Ea(0);
    function as(e) {
      for (var a = e; a !== null; ) {
        if (a.tag === 13) {
          var t = a.memoizedState;
          if (t !== null && ((t = t.dehydrated), t === null || Hn(t) || Fn(t)))
            return a;
        } else if (
          a.tag === 19 &&
          (a.memoizedProps.revealOrder === "forwards" ||
            a.memoizedProps.revealOrder === "backwards" ||
            a.memoizedProps.revealOrder === "unstable_legacy-backwards" ||
            a.memoizedProps.revealOrder === "together")
        ) {
          if ((a.flags & 128) !== 0) return a;
        } else if (a.child !== null) {
          ((a.child.return = a), (a = a.child));
          continue;
        }
        if (a === e) break;
        for (; a.sibling === null; ) {
          if (a.return === null || a.return === e) return null;
          a = a.return;
        }
        ((a.sibling.return = a.return), (a = a.sibling));
      }
      return null;
    }
    var ut = 0,
      N = null,
      ee = null,
      he = null,
      ts = !1,
      uu = !1,
      gl = !1,
      ls = 0,
      Lo = 0,
      ou = null,
      dC = 0;
    function ie() {
      throw Error(v(321));
    }
    function ci(e, a) {
      if (a === null) return !1;
      for (var t = 0; t < a.length && t < e.length; t++)
        if (!aa(e[t], a[t])) return !1;
      return !0;
    }
    function mi(e, a, t, l, u, o) {
      return (
        (ut = o),
        (N = a),
        (a.memoizedState = null),
        (a.updateQueue = null),
        (a.lanes = 0),
        (R.H = e === null || e.memoizedState === null ? Bp : bi),
        (gl = !1),
        (o = t(l, u)),
        (gl = !1),
        uu && (o = tp(a, t, l, u)),
        ap(e),
        o
      );
    }
    function ap(e) {
      R.H = go;
      var a = ee !== null && ee.next !== null;
      if (((ut = 0), (he = ee = N = null), (ts = !1), (Lo = 0), (ou = null), a))
        throw Error(v(300));
      e === null ||
        xe ||
        ((e = e.dependencies), e !== null && Wd(e) && (xe = !0));
    }
    function tp(e, a, t, l) {
      N = e;
      var u = 0;
      do {
        if ((uu && (ou = null), (Lo = 0), (uu = !1), 25 <= u))
          throw Error(v(301));
        if (((u += 1), (he = ee = null), e.updateQueue != null)) {
          var o = e.updateQueue;
          ((o.lastEffect = null),
            (o.events = null),
            (o.stores = null),
            o.memoCache != null && (o.memoCache.index = 0));
        }
        ((R.H = zp), (o = a(t, l)));
      } while (uu);
      return o;
    }
    function sC() {
      var e = R.H,
        a = e.useState()[0];
      return (
        (a = typeof a.then == "function" ? To(a) : a),
        (e = e.useState()[0]),
        (ee !== null ? ee.memoizedState : null) !== e && (N.flags |= 1024),
        a
      );
    }
    function pi() {
      var e = ls !== 0;
      return ((ls = 0), e);
    }
    function hi(e, a, t) {
      ((a.updateQueue = e.updateQueue), (a.flags &= -2053), (e.lanes &= ~t));
    }
    function Li(e) {
      if (ts) {
        for (e = e.memoizedState; e !== null; ) {
          var a = e.queue;
          (a !== null && (a.pending = null), (e = e.next));
        }
        ts = !1;
      }
      ((ut = 0), (he = ee = N = null), (uu = !1), (Lo = ls = 0), (ou = null));
    }
    function qe() {
      var e = {
        memoizedState: null,
        baseState: null,
        baseQueue: null,
        queue: null,
        next: null,
      };
      return (
        he === null ? (N.memoizedState = he = e) : (he = he.next = e),
        he
      );
    }
    function pe() {
      if (ee === null) {
        var e = N.alternate;
        e = e !== null ? e.memoizedState : null;
      } else e = ee.next;
      var a = he === null ? N.memoizedState : he.next;
      if (a !== null) ((he = a), (ee = e));
      else {
        if (e === null)
          throw N.alternate === null ? Error(v(467)) : Error(v(310));
        ((ee = e),
          (e = {
            memoizedState: ee.memoizedState,
            baseState: ee.baseState,
            baseQueue: ee.baseQueue,
            queue: ee.queue,
            next: null,
          }),
          he === null ? (N.memoizedState = he = e) : (he = he.next = e));
      }
      return he;
    }
    function ks() {
      return { lastEffect: null, events: null, stores: null, memoCache: null };
    }
    function To(e) {
      var a = Lo;
      return (
        (Lo += 1),
        ou === null && (ou = []),
        (e = Qm(ou, e, a)),
        (a = N),
        (he === null ? a.memoizedState : he.next) === null &&
          ((a = a.alternate),
          (R.H = a === null || a.memoizedState === null ? Bp : bi)),
        e
      );
    }
    function Ms(e) {
      if (e !== null && typeof e == "object") {
        if (typeof e.then == "function") return To(e);
        if (e.$$typeof === Ja) return Me(e);
      }
      throw Error(v(438, String(e)));
    }
    function gi(e) {
      var a = null,
        t = N.updateQueue;
      if ((t !== null && (a = t.memoCache), a == null)) {
        var l = N.alternate;
        l !== null &&
          ((l = l.updateQueue),
          l !== null &&
            ((l = l.memoCache),
            l != null &&
              (a = {
                data: l.data.map(function (u) {
                  return u.slice();
                }),
                index: 0,
              })));
      }
      if (
        (a == null && (a = { data: [], index: 0 }),
        t === null && ((t = ks()), (N.updateQueue = t)),
        (t.memoCache = a),
        (t = a.data[a.index]),
        t === void 0)
      )
        for (t = a.data[a.index] = Array(e), l = 0; l < e; l++) t[l] = Yg;
      return (a.index++, t);
    }
    function ot(e, a) {
      return typeof a == "function" ? a(e) : a;
    }
    function Ed(e) {
      var a = pe();
      return xi(a, ee, e);
    }
    function xi(e, a, t) {
      var l = e.queue;
      if (l === null) throw Error(v(311));
      l.lastRenderedReducer = t;
      var u = e.baseQueue,
        o = l.pending;
      if (o !== null) {
        if (u !== null) {
          var d = u.next;
          ((u.next = o.next), (o.next = d));
        }
        ((a.baseQueue = u = o), (l.pending = null));
      }
      if (((o = e.baseState), u === null)) e.memoizedState = o;
      else {
        a = u.next;
        var f = (d = null),
          n = null,
          p = a,
          h = !1;
        do {
          var x = p.lane & -536870913;
          if (x !== p.lane ? (X & x) === x : (ut & x) === x) {
            var m = p.revertLane;
            if (m === 0)
              (n !== null &&
                (n = n.next =
                  {
                    lane: 0,
                    revertLane: 0,
                    gesture: null,
                    action: p.action,
                    hasEagerState: p.hasEagerState,
                    eagerState: p.eagerState,
                    next: null,
                  }),
                x === nu && (h = !0));
            else if ((ut & m) === m) {
              ((p = p.next), m === nu && (h = !0));
              continue;
            } else
              ((x = {
                lane: 0,
                revertLane: p.revertLane,
                gesture: null,
                action: p.action,
                hasEagerState: p.hasEagerState,
                eagerState: p.eagerState,
                next: null,
              }),
                n === null ? ((f = n = x), (d = o)) : (n = n.next = x),
                (N.lanes |= m),
                (Ut |= m));
            ((x = p.action),
              gl && t(o, x),
              (o = p.hasEagerState ? p.eagerState : t(o, x)));
          } else
            ((m = {
              lane: x,
              revertLane: p.revertLane,
              gesture: p.gesture,
              action: p.action,
              hasEagerState: p.hasEagerState,
              eagerState: p.eagerState,
              next: null,
            }),
              n === null ? ((f = n = m), (d = o)) : (n = n.next = m),
              (N.lanes |= x),
              (Ut |= x));
          p = p.next;
        } while (p !== null && p !== a);
        if (
          (n === null ? (d = o) : (n.next = f),
          !aa(o, e.memoizedState) && ((xe = !0), h && ((t = tu), t !== null)))
        )
          throw t;
        ((e.memoizedState = o),
          (e.baseState = d),
          (e.baseQueue = n),
          (l.lastRenderedState = o));
      }
      return (u === null && (l.lanes = 0), [e.memoizedState, l.dispatch]);
    }
    function Tf(e) {
      var a = pe(),
        t = a.queue;
      if (t === null) throw Error(v(311));
      t.lastRenderedReducer = e;
      var l = t.dispatch,
        u = t.pending,
        o = a.memoizedState;
      if (u !== null) {
        t.pending = null;
        var d = (u = u.next);
        do ((o = e(o, d.action)), (d = d.next));
        while (d !== u);
        (aa(o, a.memoizedState) || (xe = !0),
          (a.memoizedState = o),
          a.baseQueue === null && (a.baseState = o),
          (t.lastRenderedState = o));
      }
      return [o, l];
    }
    function lp(e, a, t) {
      var l = N,
        u = pe(),
        o = Y;
      if (o) {
        if (t === void 0) throw Error(v(407));
        t = t();
      } else t = a();
      var d = !aa((ee || u).memoizedState, t);
      if (
        (d && ((u.memoizedState = t), (xe = !0)),
        (u = u.queue),
        Ci(dp.bind(null, l, u, e), [e]),
        u.getSnapshot !== a || d || (he !== null && he.memoizedState.tag & 1))
      ) {
        if (
          ((l.flags |= 2048),
          ru(9, { destroy: void 0 }, op.bind(null, l, u, t, a), null),
          ae === null)
        )
          throw Error(v(349));
        o || (ut & 127) !== 0 || up(l, a, t);
      }
      return t;
    }
    function up(e, a, t) {
      ((e.flags |= 16384),
        (e = { getSnapshot: a, value: t }),
        (a = N.updateQueue),
        a === null
          ? ((a = ks()), (N.updateQueue = a), (a.stores = [e]))
          : ((t = a.stores), t === null ? (a.stores = [e]) : t.push(e)));
    }
    function op(e, a, t, l) {
      ((a.value = t), (a.getSnapshot = l), sp(a) && fp(e));
    }
    function dp(e, a, t) {
      return t(function () {
        sp(a) && fp(e);
      });
    }
    function sp(e) {
      var a = e.getSnapshot;
      e = e.value;
      try {
        var t = a();
        return !aa(e, t);
      } catch {
        return !0;
      }
    }
    function fp(e) {
      var a = vl(e, 2);
      a !== null && _e(a, e, 2);
    }
    function Sn(e) {
      var a = qe();
      if (typeof e == "function") {
        var t = e;
        if (((e = t()), gl)) {
          St(!0);
          try {
            t();
          } finally {
            St(!1);
          }
        }
      }
      return (
        (a.memoizedState = a.baseState = e),
        (a.queue = {
          pending: null,
          lanes: 0,
          dispatch: null,
          lastRenderedReducer: ot,
          lastRenderedState: e,
        }),
        a
      );
    }
    function np(e, a, t, l) {
      return ((e.baseState = t), xi(e, ee, typeof l == "function" ? l : ot));
    }
    function fC(e, a, t, l, u) {
      if (Ds(e)) throw Error(v(485));
      if (((e = a.action), e !== null)) {
        var o = {
          payload: u,
          action: e,
          next: null,
          isTransition: !0,
          status: "pending",
          value: null,
          reason: null,
          listeners: [],
          then: function (d) {
            o.listeners.push(d);
          },
        };
        (R.T !== null ? t(!0) : (o.isTransition = !1),
          l(o),
          (t = a.pending),
          t === null
            ? ((o.next = a.pending = o), ip(a, o))
            : ((o.next = t.next), (a.pending = t.next = o)));
      }
    }
    function ip(e, a) {
      var t = a.action,
        l = a.payload,
        u = e.state;
      if (a.isTransition) {
        var o = R.T,
          d = {};
        R.T = d;
        try {
          var f = t(u, l),
            n = R.S;
          (n !== null && n(d, f), nc(e, a, f));
        } catch (p) {
          vn(e, a, p);
        } finally {
          (o !== null && d.types !== null && (o.types = d.types), (R.T = o));
        }
      } else
        try {
          ((o = t(u, l)), nc(e, a, o));
        } catch (p) {
          vn(e, a, p);
        }
    }
    function nc(e, a, t) {
      t !== null && typeof t == "object" && typeof t.then == "function"
        ? t.then(
            function (l) {
              ic(e, a, l);
            },
            function (l) {
              return vn(e, a, l);
            },
          )
        : ic(e, a, t);
    }
    function ic(e, a, t) {
      ((a.status = "fulfilled"),
        (a.value = t),
        rp(a),
        (e.state = t),
        (a = e.pending),
        a !== null &&
          ((t = a.next),
          t === a
            ? (e.pending = null)
            : ((t = t.next), (a.next = t), ip(e, t))));
    }
    function vn(e, a, t) {
      var l = e.pending;
      if (((e.pending = null), l !== null)) {
        l = l.next;
        do ((a.status = "rejected"), (a.reason = t), rp(a), (a = a.next));
        while (a !== l);
      }
      e.action = null;
    }
    function rp(e) {
      e = e.listeners;
      for (var a = 0; a < e.length; a++) (0, e[a])();
    }
    function cp(e, a) {
      return a;
    }
    function rc(e, a) {
      if (Y) {
        var t = ae.formState;
        if (t !== null) {
          e: {
            var l = N;
            if (Y) {
              if (oe) {
                a: {
                  for (var u = oe, o = ha; u.nodeType !== 8; ) {
                    if (!o) {
                      u = null;
                      break a;
                    }
                    if (((u = ga(u.nextSibling)), u === null)) {
                      u = null;
                      break a;
                    }
                  }
                  ((o = u.data), (u = o === "F!" || o === "F" ? u : null));
                }
                if (u) {
                  ((oe = ga(u.nextSibling)), (l = u.data === "F!"));
                  break e;
                }
              }
              qt(l);
            }
            l = !1;
          }
          l && (a = t[0]);
        }
      }
      return (
        (t = qe()),
        (t.memoizedState = t.baseState = a),
        (l = {
          pending: null,
          lanes: 0,
          dispatch: null,
          lastRenderedReducer: cp,
          lastRenderedState: a,
        }),
        (t.queue = l),
        (t = wp.bind(null, N, l)),
        (l.dispatch = t),
        (l = Sn(!1)),
        (o = Ii.bind(null, N, !1, l.queue)),
        (l = qe()),
        (u = { state: a, dispatch: null, action: e, pending: null }),
        (l.queue = u),
        (t = fC.bind(null, N, u, o, t)),
        (u.dispatch = t),
        (l.memoizedState = e),
        [a, t, !1]
      );
    }
    function cc(e) {
      var a = pe();
      return mp(a, ee, e);
    }
    function mp(e, a, t) {
      if (
        ((a = xi(e, a, cp)[0]),
        (e = Ed(ot)[0]),
        typeof a == "object" && a !== null && typeof a.then == "function")
      )
        try {
          var l = To(a);
        } catch (d) {
          throw d === yu ? As : d;
        }
      else l = a;
      a = pe();
      var u = a.queue,
        o = u.dispatch;
      return (
        t !== a.memoizedState &&
          ((N.flags |= 2048),
          ru(9, { destroy: void 0 }, nC.bind(null, u, t), null)),
        [l, o, e]
      );
    }
    function nC(e, a) {
      e.action = a;
    }
    function mc(e) {
      var a = pe(),
        t = ee;
      if (t !== null) return mp(a, t, e);
      (pe(), (a = a.memoizedState), (t = pe()));
      var l = t.queue.dispatch;
      return ((t.memoizedState = e), [a, l, !1]);
    }
    function ru(e, a, t, l) {
      return (
        (e = { tag: e, create: t, deps: l, inst: a, next: null }),
        (a = N.updateQueue),
        a === null && ((a = ks()), (N.updateQueue = a)),
        (t = a.lastEffect),
        t === null
          ? (a.lastEffect = e.next = e)
          : ((l = t.next), (t.next = e), (e.next = l), (a.lastEffect = e)),
        e
      );
    }
    function pp() {
      return pe().memoizedState;
    }
    function qd(e, a, t, l) {
      var u = qe();
      ((N.flags |= e),
        (u.memoizedState = ru(
          1 | a,
          { destroy: void 0 },
          t,
          l === void 0 ? null : l,
        )));
    }
    function ws(e, a, t, l) {
      var u = pe();
      l = l === void 0 ? null : l;
      var o = u.memoizedState.inst;
      ee !== null && l !== null && ci(l, ee.memoizedState.deps)
        ? (u.memoizedState = ru(a, o, t, l))
        : ((N.flags |= e), (u.memoizedState = ru(1 | a, o, t, l)));
    }
    function pc(e, a) {
      qd(8390656, 8, e, a);
    }
    function Ci(e, a) {
      ws(2048, 8, e, a);
    }
    function iC(e) {
      N.flags |= 4;
      var a = N.updateQueue;
      if (a === null) ((a = ks()), (N.updateQueue = a), (a.events = [e]));
      else {
        var t = a.events;
        t === null ? (a.events = [e]) : t.push(e);
      }
    }
    function hp(e) {
      var a = pe().memoizedState;
      return (
        iC({ ref: a, nextImpl: e }),
        function () {
          if ((Q & 2) !== 0) throw Error(v(440));
          return a.impl.apply(void 0, arguments);
        }
      );
    }
    function Lp(e, a) {
      return ws(4, 2, e, a);
    }
    function gp(e, a) {
      return ws(4, 4, e, a);
    }
    function xp(e, a) {
      if (typeof a == "function") {
        e = e();
        var t = a(e);
        return function () {
          typeof t == "function" ? t() : a(null);
        };
      }
      if (a != null)
        return (
          (e = e()),
          (a.current = e),
          function () {
            a.current = null;
          }
        );
    }
    function Cp(e, a, t) {
      ((t = t != null ? t.concat([e]) : null),
        ws(4, 4, xp.bind(null, a, e), t));
    }
    function yi() {}
    function yp(e, a) {
      var t = pe();
      a = a === void 0 ? null : a;
      var l = t.memoizedState;
      return a !== null && ci(a, l[1]) ? l[0] : ((t.memoizedState = [e, a]), e);
    }
    function Sp(e, a) {
      var t = pe();
      a = a === void 0 ? null : a;
      var l = t.memoizedState;
      if (a !== null && ci(a, l[1])) return l[0];
      if (((l = e()), gl)) {
        St(!0);
        try {
          e();
        } finally {
          St(!1);
        }
      }
      return ((t.memoizedState = [l, a]), l);
    }
    function Si(e, a, t) {
      return t === void 0 || ((ut & 1073741824) !== 0 && (X & 261930) === 0)
        ? (e.memoizedState = a)
        : ((e.memoizedState = t), (e = nh()), (N.lanes |= e), (Ut |= e), t);
    }
    function vp(e, a, t, l) {
      return aa(t, a)
        ? t
        : iu.current !== null
          ? ((e = Si(e, t, l)), aa(e, a) || (xe = !0), e)
          : (ut & 42) === 0 || ((ut & 1073741824) !== 0 && (X & 261930) === 0)
            ? ((xe = !0), (e.memoizedState = t))
            : ((e = nh()), (N.lanes |= e), (Ut |= e), a);
    }
    function Ip(e, a, t, l, u) {
      var o = K.p;
      K.p = o !== 0 && 8 > o ? o : 8;
      var d = R.T,
        f = {};
      ((R.T = f), Ii(e, !1, a, t));
      try {
        var n = u(),
          p = R.S;
        if (
          (p !== null && p(f, n),
          n !== null && typeof n == "object" && typeof n.then == "function")
        ) {
          var h = oC(n, l);
          to(e, a, h, ea(e));
        } else to(e, a, l, ea(e));
      } catch (x) {
        to(e, a, { then: function () {}, status: "rejected", reason: x }, ea());
      } finally {
        ((K.p = o),
          d !== null && f.types !== null && (d.types = f.types),
          (R.T = d));
      }
    }
    function rC() {}
    function In(e, a, t, l) {
      if (e.tag !== 5) throw Error(v(476));
      var u = bp(e).queue;
      Ip(
        e,
        u,
        a,
        nl,
        t === null
          ? rC
          : function () {
              return (Ap(e), t(l));
            },
      );
    }
    function bp(e) {
      var a = e.memoizedState;
      if (a !== null) return a;
      a = {
        memoizedState: nl,
        baseState: nl,
        baseQueue: null,
        queue: {
          pending: null,
          lanes: 0,
          dispatch: null,
          lastRenderedReducer: ot,
          lastRenderedState: nl,
        },
        next: null,
      };
      var t = {};
      return (
        (a.next = {
          memoizedState: t,
          baseState: t,
          baseQueue: null,
          queue: {
            pending: null,
            lanes: 0,
            dispatch: null,
            lastRenderedReducer: ot,
            lastRenderedState: t,
          },
          next: null,
        }),
        (e.memoizedState = a),
        (e = e.alternate),
        e !== null && (e.memoizedState = a),
        a
      );
    }
    function Ap(e) {
      var a = bp(e);
      (a.next === null && (a = e.alternate.memoizedState),
        to(e, a.next.queue, {}, ea()));
    }
    function vi() {
      return Me(yo);
    }
    function kp() {
      return pe().memoizedState;
    }
    function Mp() {
      return pe().memoizedState;
    }
    function cC(e) {
      for (var a = e.return; a !== null; ) {
        switch (a.tag) {
          case 24:
          case 3:
            var t = ea();
            e = wt(t);
            var l = Dt(a, e, t);
            (l !== null && (_e(l, a, t), $u(l, a, t)),
              (a = { cache: si() }),
              (e.payload = a));
            return;
        }
        a = a.return;
      }
    }
    function mC(e, a, t) {
      var l = ea();
      ((t = {
        lane: l,
        revertLane: 0,
        gesture: null,
        action: t,
        hasEagerState: !1,
        eagerState: null,
        next: null,
      }),
        Ds(e)
          ? Dp(a, t)
          : ((t = li(e, a, t, l)), t !== null && (_e(t, e, l), Tp(t, a, l))));
    }
    function wp(e, a, t) {
      var l = ea();
      to(e, a, t, l);
    }
    function to(e, a, t, l) {
      var u = {
        lane: l,
        revertLane: 0,
        gesture: null,
        action: t,
        hasEagerState: !1,
        eagerState: null,
        next: null,
      };
      if (Ds(e)) Dp(a, u);
      else {
        var o = e.alternate;
        if (
          e.lanes === 0 &&
          (o === null || o.lanes === 0) &&
          ((o = a.lastRenderedReducer), o !== null)
        )
          try {
            var d = a.lastRenderedState,
              f = o(d, t);
            if (((u.hasEagerState = !0), (u.eagerState = f), aa(f, d)))
              return (bs(e, a, u, 0), ae === null && Is(), !1);
          } catch {}
        if (((t = li(e, a, u, l)), t !== null))
          return (_e(t, e, l), Tp(t, a, l), !0);
      }
      return !1;
    }
    function Ii(e, a, t, l) {
      if (
        ((l = {
          lane: 2,
          revertLane: zi(),
          gesture: null,
          action: l,
          hasEagerState: !1,
          eagerState: null,
          next: null,
        }),
        Ds(e))
      ) {
        if (a) throw Error(v(479));
      } else ((a = li(e, t, l, 2)), a !== null && _e(a, e, 2));
    }
    function Ds(e) {
      var a = e.alternate;
      return e === N || (a !== null && a === N);
    }
    function Dp(e, a) {
      uu = ts = !0;
      var t = e.pending;
      (t === null ? (a.next = a) : ((a.next = t.next), (t.next = a)),
        (e.pending = a));
    }
    function Tp(e, a, t) {
      if ((t & 4194048) !== 0) {
        var l = a.lanes;
        ((l &= e.pendingLanes), (t |= l), (a.lanes = t), hm(e, t));
      }
    }
    var go = {
      readContext: Me,
      use: Ms,
      useCallback: ie,
      useContext: ie,
      useEffect: ie,
      useImperativeHandle: ie,
      useLayoutEffect: ie,
      useInsertionEffect: ie,
      useMemo: ie,
      useReducer: ie,
      useRef: ie,
      useState: ie,
      useDebugValue: ie,
      useDeferredValue: ie,
      useTransition: ie,
      useSyncExternalStore: ie,
      useId: ie,
      useHostTransitionStatus: ie,
      useFormState: ie,
      useActionState: ie,
      useOptimistic: ie,
      useMemoCache: ie,
      useCacheRefresh: ie,
    };
    go.useEffectEvent = ie;
    var Bp = {
        readContext: Me,
        use: Ms,
        useCallback: function (e, a) {
          return ((qe().memoizedState = [e, a === void 0 ? null : a]), e);
        },
        useContext: Me,
        useEffect: pc,
        useImperativeHandle: function (e, a, t) {
          ((t = t != null ? t.concat([e]) : null),
            qd(4194308, 4, xp.bind(null, a, e), t));
        },
        useLayoutEffect: function (e, a) {
          return qd(4194308, 4, e, a);
        },
        useInsertionEffect: function (e, a) {
          qd(4, 2, e, a);
        },
        useMemo: function (e, a) {
          var t = qe();
          a = a === void 0 ? null : a;
          var l = e();
          if (gl) {
            St(!0);
            try {
              e();
            } finally {
              St(!1);
            }
          }
          return ((t.memoizedState = [l, a]), l);
        },
        useReducer: function (e, a, t) {
          var l = qe();
          if (t !== void 0) {
            var u = t(a);
            if (gl) {
              St(!0);
              try {
                t(a);
              } finally {
                St(!1);
              }
            }
          } else u = a;
          return (
            (l.memoizedState = l.baseState = u),
            (e = {
              pending: null,
              lanes: 0,
              dispatch: null,
              lastRenderedReducer: e,
              lastRenderedState: u,
            }),
            (l.queue = e),
            (e = e.dispatch = mC.bind(null, N, e)),
            [l.memoizedState, e]
          );
        },
        useRef: function (e) {
          var a = qe();
          return ((e = { current: e }), (a.memoizedState = e));
        },
        useState: function (e) {
          e = Sn(e);
          var a = e.queue,
            t = wp.bind(null, N, a);
          return ((a.dispatch = t), [e.memoizedState, t]);
        },
        useDebugValue: yi,
        useDeferredValue: function (e, a) {
          var t = qe();
          return Si(t, e, a);
        },
        useTransition: function () {
          var e = Sn(!1);
          return (
            (e = Ip.bind(null, N, e.queue, !0, !1)),
            (qe().memoizedState = e),
            [!1, e]
          );
        },
        useSyncExternalStore: function (e, a, t) {
          var l = N,
            u = qe();
          if (Y) {
            if (t === void 0) throw Error(v(407));
            t = t();
          } else {
            if (((t = a()), ae === null)) throw Error(v(349));
            (X & 127) !== 0 || up(l, a, t);
          }
          u.memoizedState = t;
          var o = { value: t, getSnapshot: a };
          return (
            (u.queue = o),
            pc(dp.bind(null, l, o, e), [e]),
            (l.flags |= 2048),
            ru(9, { destroy: void 0 }, op.bind(null, l, o, t, a), null),
            t
          );
        },
        useId: function () {
          var e = qe(),
            a = ae.identifierPrefix;
          if (Y) {
            var t = za,
              l = Ba;
            ((t = (l & ~(1 << (32 - $e(l) - 1))).toString(32) + t),
              (a = "_" + a + "R_" + t),
              (t = ls++),
              0 < t && (a += "H" + t.toString(32)),
              (a += "_"));
          } else ((t = dC++), (a = "_" + a + "r_" + t.toString(32) + "_"));
          return (e.memoizedState = a);
        },
        useHostTransitionStatus: vi,
        useFormState: rc,
        useActionState: rc,
        useOptimistic: function (e) {
          var a = qe();
          a.memoizedState = a.baseState = e;
          var t = {
            pending: null,
            lanes: 0,
            dispatch: null,
            lastRenderedReducer: null,
            lastRenderedState: null,
          };
          return (
            (a.queue = t),
            (a = Ii.bind(null, N, !0, t)),
            (t.dispatch = a),
            [e, a]
          );
        },
        useMemoCache: gi,
        useCacheRefresh: function () {
          return (qe().memoizedState = cC.bind(null, N));
        },
        useEffectEvent: function (e) {
          var a = qe(),
            t = { impl: e };
          return (
            (a.memoizedState = t),
            function () {
              if ((Q & 2) !== 0) throw Error(v(440));
              return t.impl.apply(void 0, arguments);
            }
          );
        },
      },
      bi = {
        readContext: Me,
        use: Ms,
        useCallback: yp,
        useContext: Me,
        useEffect: Ci,
        useImperativeHandle: Cp,
        useInsertionEffect: Lp,
        useLayoutEffect: gp,
        useMemo: Sp,
        useReducer: Ed,
        useRef: pp,
        useState: function () {
          return Ed(ot);
        },
        useDebugValue: yi,
        useDeferredValue: function (e, a) {
          var t = pe();
          return vp(t, ee.memoizedState, e, a);
        },
        useTransition: function () {
          var e = Ed(ot)[0],
            a = pe().memoizedState;
          return [typeof e == "boolean" ? e : To(e), a];
        },
        useSyncExternalStore: lp,
        useId: kp,
        useHostTransitionStatus: vi,
        useFormState: cc,
        useActionState: cc,
        useOptimistic: function (e, a) {
          var t = pe();
          return np(t, ee, e, a);
        },
        useMemoCache: gi,
        useCacheRefresh: Mp,
      };
    bi.useEffectEvent = hp;
    var zp = {
      readContext: Me,
      use: Ms,
      useCallback: yp,
      useContext: Me,
      useEffect: Ci,
      useImperativeHandle: Cp,
      useInsertionEffect: Lp,
      useLayoutEffect: gp,
      useMemo: Sp,
      useReducer: Tf,
      useRef: pp,
      useState: function () {
        return Tf(ot);
      },
      useDebugValue: yi,
      useDeferredValue: function (e, a) {
        var t = pe();
        return ee === null ? Si(t, e, a) : vp(t, ee.memoizedState, e, a);
      },
      useTransition: function () {
        var e = Tf(ot)[0],
          a = pe().memoizedState;
        return [typeof e == "boolean" ? e : To(e), a];
      },
      useSyncExternalStore: lp,
      useId: kp,
      useHostTransitionStatus: vi,
      useFormState: mc,
      useActionState: mc,
      useOptimistic: function (e, a) {
        var t = pe();
        return ee !== null
          ? np(t, ee, e, a)
          : ((t.baseState = e), [e, t.queue.dispatch]);
      },
      useMemoCache: gi,
      useCacheRefresh: Mp,
    };
    zp.useEffectEvent = hp;
    function Bf(e, a, t, l) {
      ((a = e.memoizedState),
        (t = t(l, a)),
        (t = t == null ? a : de({}, a, t)),
        (e.memoizedState = t),
        e.lanes === 0 && (e.updateQueue.baseState = t));
    }
    var bn = {
      enqueueSetState: function (e, a, t) {
        e = e._reactInternals;
        var l = ea(),
          u = wt(l);
        ((u.payload = a),
          t != null && (u.callback = t),
          (a = Dt(e, u, l)),
          a !== null && (_e(a, e, l), $u(a, e, l)));
      },
      enqueueReplaceState: function (e, a, t) {
        e = e._reactInternals;
        var l = ea(),
          u = wt(l);
        ((u.tag = 1),
          (u.payload = a),
          t != null && (u.callback = t),
          (a = Dt(e, u, l)),
          a !== null && (_e(a, e, l), $u(a, e, l)));
      },
      enqueueForceUpdate: function (e, a) {
        e = e._reactInternals;
        var t = ea(),
          l = wt(t);
        ((l.tag = 2),
          a != null && (l.callback = a),
          (a = Dt(e, l, t)),
          a !== null && (_e(a, e, t), $u(a, e, t)));
      },
    };
    function hc(e, a, t, l, u, o, d) {
      return (
        (e = e.stateNode),
        typeof e.shouldComponentUpdate == "function"
          ? e.shouldComponentUpdate(l, o, d)
          : a.prototype && a.prototype.isPureReactComponent
            ? !co(t, l) || !co(u, o)
            : !0
      );
    }
    function Lc(e, a, t, l) {
      ((e = a.state),
        typeof a.componentWillReceiveProps == "function" &&
          a.componentWillReceiveProps(t, l),
        typeof a.UNSAFE_componentWillReceiveProps == "function" &&
          a.UNSAFE_componentWillReceiveProps(t, l),
        a.state !== e && bn.enqueueReplaceState(a, a.state, null));
    }
    function xl(e, a) {
      var t = a;
      if ("ref" in a) {
        t = {};
        for (var l in a) l !== "ref" && (t[l] = a[l]);
      }
      if ((e = e.defaultProps)) {
        t === a && (t = de({}, t));
        for (var u in e) t[u] === void 0 && (t[u] = e[u]);
      }
      return t;
    }
    function Rp(e) {
      Qd(e);
    }
    function Op(e) {
      console.error(e);
    }
    function Ep(e) {
      Qd(e);
    }
    function us(e, a) {
      try {
        var t = e.onUncaughtError;
        t(a.value, { componentStack: a.stack });
      } catch (l) {
        setTimeout(function () {
          throw l;
        });
      }
    }
    function gc(e, a, t) {
      try {
        var l = e.onCaughtError;
        l(t.value, {
          componentStack: t.stack,
          errorBoundary: a.tag === 1 ? a.stateNode : null,
        });
      } catch (u) {
        setTimeout(function () {
          throw u;
        });
      }
    }
    function An(e, a, t) {
      return (
        (t = wt(t)),
        (t.tag = 3),
        (t.payload = { element: null }),
        (t.callback = function () {
          us(e, a);
        }),
        t
      );
    }
    function qp(e) {
      return ((e = wt(e)), (e.tag = 3), e);
    }
    function Np(e, a, t, l) {
      var u = t.type.getDerivedStateFromError;
      if (typeof u == "function") {
        var o = l.value;
        ((e.payload = function () {
          return u(o);
        }),
          (e.callback = function () {
            gc(a, t, l);
          }));
      }
      var d = t.stateNode;
      d !== null &&
        typeof d.componentDidCatch == "function" &&
        (e.callback = function () {
          (gc(a, t, l),
            typeof u != "function" &&
              (Tt === null ? (Tt = new Set([this])) : Tt.add(this)));
          var f = l.stack;
          this.componentDidCatch(l.value, {
            componentStack: f !== null ? f : "",
          });
        });
    }
    function pC(e, a, t, l, u) {
      if (
        ((t.flags |= 32768),
        l !== null && typeof l == "object" && typeof l.then == "function")
      ) {
        if (
          ((a = t.alternate),
          a !== null && Cu(a, t, u, !0),
          (t = ta.current),
          t !== null)
        ) {
          switch (t.tag) {
            case 31:
            case 13:
              return (
                La === null
                  ? ns()
                  : t.alternate === null && re === 0 && (re = 3),
                (t.flags &= -257),
                (t.flags |= 65536),
                (t.lanes = u),
                l === $d
                  ? (t.flags |= 16384)
                  : ((a = t.updateQueue),
                    a === null ? (t.updateQueue = new Set([l])) : a.add(l),
                    _f(e, l, u)),
                !1
              );
            case 22:
              return (
                (t.flags |= 65536),
                l === $d
                  ? (t.flags |= 16384)
                  : ((a = t.updateQueue),
                    a === null
                      ? ((a = {
                          transitions: null,
                          markerInstances: null,
                          retryQueue: new Set([l]),
                        }),
                        (t.updateQueue = a))
                      : ((t = a.retryQueue),
                        t === null ? (a.retryQueue = new Set([l])) : t.add(l)),
                    _f(e, l, u)),
                !1
              );
          }
          throw Error(v(435, t.tag));
        }
        return (_f(e, l, u), ns(), !1);
      }
      if (Y)
        return (
          (a = ta.current),
          a !== null
            ? ((a.flags & 65536) === 0 && (a.flags |= 256),
              (a.flags |= 65536),
              (a.lanes = u),
              l !== cn && ((e = Error(v(422), { cause: l })), po(pa(e, t))))
            : (l !== cn && ((a = Error(v(423), { cause: l })), po(pa(a, t))),
              (e = e.current.alternate),
              (e.flags |= 65536),
              (u &= -u),
              (e.lanes |= u),
              (l = pa(l, t)),
              (u = An(e.stateNode, l, u)),
              Df(e, u),
              re !== 4 && (re = 2)),
          !1
        );
      var o = Error(v(520), { cause: l });
      if (
        ((o = pa(o, t)),
        oo === null ? (oo = [o]) : oo.push(o),
        re !== 4 && (re = 2),
        a === null)
      )
        return !0;
      ((l = pa(l, t)), (t = a));
      do {
        switch (t.tag) {
          case 3:
            return (
              (t.flags |= 65536),
              (e = u & -u),
              (t.lanes |= e),
              (e = An(t.stateNode, l, e)),
              Df(t, e),
              !1
            );
          case 1:
            if (
              ((a = t.type),
              (o = t.stateNode),
              (t.flags & 128) === 0 &&
                (typeof a.getDerivedStateFromError == "function" ||
                  (o !== null &&
                    typeof o.componentDidCatch == "function" &&
                    (Tt === null || !Tt.has(o)))))
            )
              return (
                (t.flags |= 65536),
                (u &= -u),
                (t.lanes |= u),
                (u = qp(u)),
                Np(u, e, t, l),
                Df(t, u),
                !1
              );
        }
        t = t.return;
      } while (t !== null);
      return !1;
    }
    var Ai = Error(v(461)),
      xe = !1;
    function be(e, a, t, l) {
      a.child = e === null ? Jm(a, null, t, l) : Ll(a, e.child, t, l);
    }
    function xc(e, a, t, l, u) {
      t = t.render;
      var o = a.ref;
      if ("ref" in l) {
        var d = {};
        for (var f in l) f !== "ref" && (d[f] = l[f]);
      } else d = l;
      return (
        hl(a),
        (l = mi(e, a, t, d, o, u)),
        (f = pi()),
        e !== null && !xe
          ? (hi(e, a, u), dt(e, a, u))
          : (Y && f && oi(a), (a.flags |= 1), be(e, a, l, u), a.child)
      );
    }
    function Cc(e, a, t, l, u) {
      if (e === null) {
        var o = t.type;
        return typeof o == "function" &&
          !ui(o) &&
          o.defaultProps === void 0 &&
          t.compare === null
          ? ((a.tag = 15), (a.type = o), Up(e, a, o, l, u))
          : ((e = Rd(t.type, null, l, a, a.mode, u)),
            (e.ref = a.ref),
            (e.return = a),
            (a.child = e));
      }
      if (((o = e.child), !ki(e, u))) {
        var d = o.memoizedProps;
        if (
          ((t = t.compare),
          (t = t !== null ? t : co),
          t(d, l) && e.ref === a.ref)
        )
          return dt(e, a, u);
      }
      return (
        (a.flags |= 1),
        (e = et(o, l)),
        (e.ref = a.ref),
        (e.return = a),
        (a.child = e)
      );
    }
    function Up(e, a, t, l, u) {
      if (e !== null) {
        var o = e.memoizedProps;
        if (co(o, l) && e.ref === a.ref)
          if (((xe = !1), (a.pendingProps = l = o), ki(e, u)))
            (e.flags & 131072) !== 0 && (xe = !0);
          else return ((a.lanes = e.lanes), dt(e, a, u));
      }
      return kn(e, a, t, l, u);
    }
    function Hp(e, a, t, l) {
      var u = l.children,
        o = e !== null ? e.memoizedState : null;
      if (
        (e === null &&
          a.stateNode === null &&
          (a.stateNode = {
            _visibility: 1,
            _pendingMarkers: null,
            _retryCache: null,
            _transitions: null,
          }),
        l.mode === "hidden")
      ) {
        if ((a.flags & 128) !== 0) {
          if (((o = o !== null ? o.baseLanes | t : t), e !== null)) {
            for (l = a.child = e.child, u = 0; l !== null; )
              ((u = u | l.lanes | l.childLanes), (l = l.sibling));
            l = u & ~o;
          } else ((l = 0), (a.child = null));
          return yc(e, a, o, t, l);
        }
        if ((t & 536870912) !== 0)
          ((a.memoizedState = { baseLanes: 0, cachePool: null }),
            e !== null && Od(a, o !== null ? o.cachePool : null),
            o !== null ? fc(a, o) : Cn(),
            ep(a));
        else
          return (
            (l = a.lanes = 536870912),
            yc(e, a, o !== null ? o.baseLanes | t : t, t, l)
          );
      } else
        o !== null
          ? (Od(a, o.cachePool), fc(a, o), Ct(a), (a.memoizedState = null))
          : (e !== null && Od(a, null), Cn(), Ct(a));
      return (be(e, a, u, t), a.child);
    }
    function ju(e, a) {
      return (
        (e !== null && e.tag === 22) ||
          a.stateNode !== null ||
          (a.stateNode = {
            _visibility: 1,
            _pendingMarkers: null,
            _retryCache: null,
            _transitions: null,
          }),
        a.sibling
      );
    }
    function yc(e, a, t, l, u) {
      var o = fi();
      return (
        (o = o === null ? null : { parent: ge._currentValue, pool: o }),
        (a.memoizedState = { baseLanes: t, cachePool: o }),
        e !== null && Od(a, null),
        Cn(),
        ep(a),
        e !== null && Cu(e, a, l, !0),
        (a.childLanes = u),
        null
      );
    }
    function Nd(e, a) {
      return (
        (a = os({ mode: a.mode, children: a.children }, e.mode)),
        (a.ref = e.ref),
        (e.child = a),
        (a.return = e),
        a
      );
    }
    function Sc(e, a, t) {
      return (
        Ll(a, e.child, null, t),
        (e = Nd(a, a.pendingProps)),
        (e.flags |= 2),
        Ze(a),
        (a.memoizedState = null),
        e
      );
    }
    function hC(e, a, t) {
      var l = a.pendingProps,
        u = (a.flags & 128) !== 0;
      if (((a.flags &= -129), e === null)) {
        if (Y) {
          if (l.mode === "hidden")
            return ((e = Nd(a, l)), (a.lanes = 536870912), ju(null, e));
          if (
            (yn(a),
            (e = oe)
              ? ((e = Bh(e, ha)),
                (e = e !== null && e.data === "&" ? e : null),
                e !== null &&
                  ((a.memoizedState = {
                    dehydrated: e,
                    treeContext: Et !== null ? { id: Ba, overflow: za } : null,
                    retryLane: 536870912,
                    hydrationErrors: null,
                  }),
                  (t = Vm(e)),
                  (t.return = a),
                  (a.child = t),
                  (ke = a),
                  (oe = null)))
              : (e = null),
            e === null)
          )
            throw qt(a);
          return ((a.lanes = 536870912), null);
        }
        return Nd(a, l);
      }
      var o = e.memoizedState;
      if (o !== null) {
        var d = o.dehydrated;
        if ((yn(a), u))
          if (a.flags & 256) ((a.flags &= -257), (a = Sc(e, a, t)));
          else if (a.memoizedState !== null)
            ((a.child = e.child), (a.flags |= 128), (a = null));
          else throw Error(v(558));
        else if (
          (xe || Cu(e, a, t, !1), (u = (t & e.childLanes) !== 0), xe || u)
        ) {
          if (
            ((l = ae),
            l !== null && ((d = Lm(l, t)), d !== 0 && d !== o.retryLane))
          )
            throw ((o.retryLane = d), vl(e, d), _e(l, e, d), Ai);
          (ns(), (a = Sc(e, a, t)));
        } else
          ((e = o.treeContext),
            (oe = ga(d.nextSibling)),
            (ke = a),
            (Y = !0),
            (Mt = null),
            (ha = !1),
            e !== null && Ym(a, e),
            (a = Nd(a, l)),
            (a.flags |= 4096));
        return a;
      }
      return (
        (e = et(e.child, { mode: l.mode, children: l.children })),
        (e.ref = a.ref),
        (a.child = e),
        (e.return = a),
        e
      );
    }
    function Ud(e, a) {
      var t = a.ref;
      if (t === null) e !== null && e.ref !== null && (a.flags |= 4194816);
      else {
        if (typeof t != "function" && typeof t != "object") throw Error(v(284));
        (e === null || e.ref !== t) && (a.flags |= 4194816);
      }
    }
    function kn(e, a, t, l, u) {
      return (
        hl(a),
        (t = mi(e, a, t, l, void 0, u)),
        (l = pi()),
        e !== null && !xe
          ? (hi(e, a, u), dt(e, a, u))
          : (Y && l && oi(a), (a.flags |= 1), be(e, a, t, u), a.child)
      );
    }
    function vc(e, a, t, l, u, o) {
      return (
        hl(a),
        (a.updateQueue = null),
        (t = tp(a, l, t, u)),
        ap(e),
        (l = pi()),
        e !== null && !xe
          ? (hi(e, a, o), dt(e, a, o))
          : (Y && l && oi(a), (a.flags |= 1), be(e, a, t, o), a.child)
      );
    }
    function Ic(e, a, t, l, u) {
      if ((hl(a), a.stateNode === null)) {
        var o = Ql,
          d = t.contextType;
        (typeof d == "object" && d !== null && (o = Me(d)),
          (o = new t(l, o)),
          (a.memoizedState =
            o.state !== null && o.state !== void 0 ? o.state : null),
          (o.updater = bn),
          (a.stateNode = o),
          (o._reactInternals = a),
          (o = a.stateNode),
          (o.props = l),
          (o.state = a.memoizedState),
          (o.refs = {}),
          ii(a),
          (d = t.contextType),
          (o.context = typeof d == "object" && d !== null ? Me(d) : Ql),
          (o.state = a.memoizedState),
          (d = t.getDerivedStateFromProps),
          typeof d == "function" &&
            (Bf(a, t, d, l), (o.state = a.memoizedState)),
          typeof t.getDerivedStateFromProps == "function" ||
            typeof o.getSnapshotBeforeUpdate == "function" ||
            (typeof o.UNSAFE_componentWillMount != "function" &&
              typeof o.componentWillMount != "function") ||
            ((d = o.state),
            typeof o.componentWillMount == "function" && o.componentWillMount(),
            typeof o.UNSAFE_componentWillMount == "function" &&
              o.UNSAFE_componentWillMount(),
            d !== o.state && bn.enqueueReplaceState(o, o.state, null),
            ao(a, l, o, u),
            eo(),
            (o.state = a.memoizedState)),
          typeof o.componentDidMount == "function" && (a.flags |= 4194308),
          (l = !0));
      } else if (e === null) {
        o = a.stateNode;
        var f = a.memoizedProps,
          n = xl(t, f);
        o.props = n;
        var p = o.context,
          h = t.contextType;
        ((d = Ql), typeof h == "object" && h !== null && (d = Me(h)));
        var x = t.getDerivedStateFromProps;
        ((h =
          typeof x == "function" ||
          typeof o.getSnapshotBeforeUpdate == "function"),
          (f = a.pendingProps !== f),
          h ||
            (typeof o.UNSAFE_componentWillReceiveProps != "function" &&
              typeof o.componentWillReceiveProps != "function") ||
            ((f || p !== d) && Lc(a, o, l, d)),
          (Lt = !1));
        var m = a.memoizedState;
        ((o.state = m),
          ao(a, l, o, u),
          eo(),
          (p = a.memoizedState),
          f || m !== p || Lt
            ? (typeof x == "function" &&
                (Bf(a, t, x, l), (p = a.memoizedState)),
              (n = Lt || hc(a, t, n, l, m, p, d))
                ? (h ||
                    (typeof o.UNSAFE_componentWillMount != "function" &&
                      typeof o.componentWillMount != "function") ||
                    (typeof o.componentWillMount == "function" &&
                      o.componentWillMount(),
                    typeof o.UNSAFE_componentWillMount == "function" &&
                      o.UNSAFE_componentWillMount()),
                  typeof o.componentDidMount == "function" &&
                    (a.flags |= 4194308))
                : (typeof o.componentDidMount == "function" &&
                    (a.flags |= 4194308),
                  (a.memoizedProps = l),
                  (a.memoizedState = p)),
              (o.props = l),
              (o.state = p),
              (o.context = d),
              (l = n))
            : (typeof o.componentDidMount == "function" && (a.flags |= 4194308),
              (l = !1)));
      } else {
        ((o = a.stateNode),
          gn(e, a),
          (d = a.memoizedProps),
          (h = xl(t, d)),
          (o.props = h),
          (x = a.pendingProps),
          (m = o.context),
          (p = t.contextType),
          (n = Ql),
          typeof p == "object" && p !== null && (n = Me(p)),
          (f = t.getDerivedStateFromProps),
          (p =
            typeof f == "function" ||
            typeof o.getSnapshotBeforeUpdate == "function") ||
            (typeof o.UNSAFE_componentWillReceiveProps != "function" &&
              typeof o.componentWillReceiveProps != "function") ||
            ((d !== x || m !== n) && Lc(a, o, l, n)),
          (Lt = !1),
          (m = a.memoizedState),
          (o.state = m),
          ao(a, l, o, u),
          eo());
        var L = a.memoizedState;
        d !== x ||
        m !== L ||
        Lt ||
        (e !== null && e.dependencies !== null && Wd(e.dependencies))
          ? (typeof f == "function" && (Bf(a, t, f, l), (L = a.memoizedState)),
            (h =
              Lt ||
              hc(a, t, h, l, m, L, n) ||
              (e !== null && e.dependencies !== null && Wd(e.dependencies)))
              ? (p ||
                  (typeof o.UNSAFE_componentWillUpdate != "function" &&
                    typeof o.componentWillUpdate != "function") ||
                  (typeof o.componentWillUpdate == "function" &&
                    o.componentWillUpdate(l, L, n),
                  typeof o.UNSAFE_componentWillUpdate == "function" &&
                    o.UNSAFE_componentWillUpdate(l, L, n)),
                typeof o.componentDidUpdate == "function" && (a.flags |= 4),
                typeof o.getSnapshotBeforeUpdate == "function" &&
                  (a.flags |= 1024))
              : (typeof o.componentDidUpdate != "function" ||
                  (d === e.memoizedProps && m === e.memoizedState) ||
                  (a.flags |= 4),
                typeof o.getSnapshotBeforeUpdate != "function" ||
                  (d === e.memoizedProps && m === e.memoizedState) ||
                  (a.flags |= 1024),
                (a.memoizedProps = l),
                (a.memoizedState = L)),
            (o.props = l),
            (o.state = L),
            (o.context = n),
            (l = h))
          : (typeof o.componentDidUpdate != "function" ||
              (d === e.memoizedProps && m === e.memoizedState) ||
              (a.flags |= 4),
            typeof o.getSnapshotBeforeUpdate != "function" ||
              (d === e.memoizedProps && m === e.memoizedState) ||
              (a.flags |= 1024),
            (l = !1));
      }
      return (
        (o = l),
        Ud(e, a),
        (l = (a.flags & 128) !== 0),
        o || l
          ? ((o = a.stateNode),
            (t =
              l && typeof t.getDerivedStateFromError != "function"
                ? null
                : o.render()),
            (a.flags |= 1),
            e !== null && l
              ? ((a.child = Ll(a, e.child, null, u)),
                (a.child = Ll(a, null, t, u)))
              : be(e, a, t, u),
            (a.memoizedState = o.state),
            (e = a.child))
          : (e = dt(e, a, u)),
        e
      );
    }
    function bc(e, a, t, l) {
      return (pl(), (a.flags |= 256), be(e, a, t, l), a.child);
    }
    var zf = {
      dehydrated: null,
      treeContext: null,
      retryLane: 0,
      hydrationErrors: null,
    };
    function Rf(e) {
      return { baseLanes: e, cachePool: Zm() };
    }
    function Of(e, a, t) {
      return ((e = e !== null ? e.childLanes & ~t : 0), a && (e |= Ke), e);
    }
    function Fp(e, a, t) {
      var l = a.pendingProps,
        u = !1,
        o = (a.flags & 128) !== 0,
        d;
      if (
        ((d = o) ||
          (d =
            e !== null && e.memoizedState === null
              ? !1
              : (me.current & 2) !== 0),
        d && ((u = !0), (a.flags &= -129)),
        (d = (a.flags & 32) !== 0),
        (a.flags &= -33),
        e === null)
      ) {
        if (Y) {
          if (
            (u ? xt(a) : Ct(a),
            (e = oe)
              ? ((e = Bh(e, ha)),
                (e = e !== null && e.data !== "&" ? e : null),
                e !== null &&
                  ((a.memoizedState = {
                    dehydrated: e,
                    treeContext: Et !== null ? { id: Ba, overflow: za } : null,
                    retryLane: 536870912,
                    hydrationErrors: null,
                  }),
                  (t = Vm(e)),
                  (t.return = a),
                  (a.child = t),
                  (ke = a),
                  (oe = null)))
              : (e = null),
            e === null)
          )
            throw qt(a);
          return (Fn(e) ? (a.lanes = 32) : (a.lanes = 536870912), null);
        }
        var f = l.children;
        return (
          (l = l.fallback),
          u
            ? (Ct(a),
              (u = a.mode),
              (f = os({ mode: "hidden", children: f }, u)),
              (l = il(l, u, t, null)),
              (f.return = a),
              (l.return = a),
              (f.sibling = l),
              (a.child = f),
              (l = a.child),
              (l.memoizedState = Rf(t)),
              (l.childLanes = Of(e, d, t)),
              (a.memoizedState = zf),
              ju(null, l))
            : (xt(a), Mn(a, f))
        );
      }
      var n = e.memoizedState;
      if (n !== null && ((f = n.dehydrated), f !== null)) {
        if (o)
          a.flags & 256
            ? (xt(a), (a.flags &= -257), (a = Ef(e, a, t)))
            : a.memoizedState !== null
              ? (Ct(a), (a.child = e.child), (a.flags |= 128), (a = null))
              : (Ct(a),
                (f = l.fallback),
                (u = a.mode),
                (l = os({ mode: "visible", children: l.children }, u)),
                (f = il(f, u, t, null)),
                (f.flags |= 2),
                (l.return = a),
                (f.return = a),
                (l.sibling = f),
                (a.child = l),
                Ll(a, e.child, null, t),
                (l = a.child),
                (l.memoizedState = Rf(t)),
                (l.childLanes = Of(e, d, t)),
                (a.memoizedState = zf),
                (a = ju(null, l)));
        else if ((xt(a), Fn(f))) {
          if (((d = f.nextSibling && f.nextSibling.dataset), d)) var p = d.dgst;
          ((d = p),
            (l = Error(v(419))),
            (l.stack = ""),
            (l.digest = d),
            po({ value: l, source: null, stack: null }),
            (a = Ef(e, a, t)));
        } else if (
          (xe || Cu(e, a, t, !1), (d = (t & e.childLanes) !== 0), xe || d)
        ) {
          if (
            ((d = ae),
            d !== null && ((l = Lm(d, t)), l !== 0 && l !== n.retryLane))
          )
            throw ((n.retryLane = l), vl(e, l), _e(d, e, l), Ai);
          (Hn(f) || ns(), (a = Ef(e, a, t)));
        } else
          Hn(f)
            ? ((a.flags |= 192), (a.child = e.child), (a = null))
            : ((e = n.treeContext),
              (oe = ga(f.nextSibling)),
              (ke = a),
              (Y = !0),
              (Mt = null),
              (ha = !1),
              e !== null && Ym(a, e),
              (a = Mn(a, l.children)),
              (a.flags |= 4096));
        return a;
      }
      return u
        ? (Ct(a),
          (f = l.fallback),
          (u = a.mode),
          (n = e.child),
          (p = n.sibling),
          (l = et(n, { mode: "hidden", children: l.children })),
          (l.subtreeFlags = n.subtreeFlags & 65011712),
          p !== null
            ? (f = et(p, f))
            : ((f = il(f, u, t, null)), (f.flags |= 2)),
          (f.return = a),
          (l.return = a),
          (l.sibling = f),
          (a.child = l),
          ju(null, l),
          (l = a.child),
          (f = e.child.memoizedState),
          f === null
            ? (f = Rf(t))
            : ((u = f.cachePool),
              u !== null
                ? ((n = ge._currentValue),
                  (u = u.parent !== n ? { parent: n, pool: n } : u))
                : (u = Zm()),
              (f = { baseLanes: f.baseLanes | t, cachePool: u })),
          (l.memoizedState = f),
          (l.childLanes = Of(e, d, t)),
          (a.memoizedState = zf),
          ju(e.child, l))
        : (xt(a),
          (t = e.child),
          (e = t.sibling),
          (t = et(t, { mode: "visible", children: l.children })),
          (t.return = a),
          (t.sibling = null),
          e !== null &&
            ((d = a.deletions),
            d === null ? ((a.deletions = [e]), (a.flags |= 16)) : d.push(e)),
          (a.child = t),
          (a.memoizedState = null),
          t);
    }
    function Mn(e, a) {
      return (
        (a = os({ mode: "visible", children: a }, e.mode)),
        (a.return = e),
        (e.child = a)
      );
    }
    function os(e, a) {
      return ((e = Qe(22, e, null, a)), (e.lanes = 0), e);
    }
    function Ef(e, a, t) {
      return (
        Ll(a, e.child, null, t),
        (e = Mn(a, a.pendingProps.children)),
        (e.flags |= 2),
        (a.memoizedState = null),
        e
      );
    }
    function Ac(e, a, t) {
      e.lanes |= a;
      var l = e.alternate;
      (l !== null && (l.lanes |= a), pn(e.return, a, t));
    }
    function qf(e, a, t, l, u, o) {
      var d = e.memoizedState;
      d === null
        ? (e.memoizedState = {
            isBackwards: a,
            rendering: null,
            renderingStartTime: 0,
            last: l,
            tail: t,
            tailMode: u,
            treeForkCount: o,
          })
        : ((d.isBackwards = a),
          (d.rendering = null),
          (d.renderingStartTime = 0),
          (d.last = l),
          (d.tail = t),
          (d.tailMode = u),
          (d.treeForkCount = o));
    }
    function Pp(e, a, t) {
      var l = a.pendingProps,
        u = l.revealOrder,
        o = l.tail;
      l = l.children;
      var d = me.current,
        f = (d & 2) !== 0;
      if (
        (f ? ((d = (d & 1) | 2), (a.flags |= 128)) : (d &= 1),
        te(me, d),
        be(e, a, l, t),
        (l = Y ? mo : 0),
        !f && e !== null && (e.flags & 128) !== 0)
      )
        e: for (e = a.child; e !== null; ) {
          if (e.tag === 13) e.memoizedState !== null && Ac(e, t, a);
          else if (e.tag === 19) Ac(e, t, a);
          else if (e.child !== null) {
            ((e.child.return = e), (e = e.child));
            continue;
          }
          if (e === a) break e;
          for (; e.sibling === null; ) {
            if (e.return === null || e.return === a) break e;
            e = e.return;
          }
          ((e.sibling.return = e.return), (e = e.sibling));
        }
      switch (u) {
        case "forwards":
          for (t = a.child, u = null; t !== null; )
            ((e = t.alternate),
              e !== null && as(e) === null && (u = t),
              (t = t.sibling));
          ((t = u),
            t === null
              ? ((u = a.child), (a.child = null))
              : ((u = t.sibling), (t.sibling = null)),
            qf(a, !1, u, t, o, l));
          break;
        case "backwards":
        case "unstable_legacy-backwards":
          for (t = null, u = a.child, a.child = null; u !== null; ) {
            if (((e = u.alternate), e !== null && as(e) === null)) {
              a.child = u;
              break;
            }
            ((e = u.sibling), (u.sibling = t), (t = u), (u = e));
          }
          qf(a, !0, t, null, o, l);
          break;
        case "together":
          qf(a, !1, null, null, void 0, l);
          break;
        default:
          a.memoizedState = null;
      }
      return a.child;
    }
    function dt(e, a, t) {
      if (
        (e !== null && (a.dependencies = e.dependencies),
        (Ut |= a.lanes),
        (t & a.childLanes) === 0)
      )
        if (e !== null) {
          if ((Cu(e, a, t, !1), (t & a.childLanes) === 0)) return null;
        } else return null;
      if (e !== null && a.child !== e.child) throw Error(v(153));
      if (a.child !== null) {
        for (
          e = a.child, t = et(e, e.pendingProps), a.child = t, t.return = a;
          e.sibling !== null;
        )
          ((e = e.sibling),
            (t = t.sibling = et(e, e.pendingProps)),
            (t.return = a));
        t.sibling = null;
      }
      return a.child;
    }
    function ki(e, a) {
      return (e.lanes & a) !== 0
        ? !0
        : ((e = e.dependencies), !!(e !== null && Wd(e)));
    }
    function LC(e, a, t) {
      switch (a.tag) {
        case 3:
          (Xd(a, a.stateNode.containerInfo),
            gt(a, ge, e.memoizedState.cache),
            pl());
          break;
        case 27:
        case 5:
          en(a);
          break;
        case 4:
          Xd(a, a.stateNode.containerInfo);
          break;
        case 10:
          gt(a, a.type, a.memoizedProps.value);
          break;
        case 31:
          if (a.memoizedState !== null) return ((a.flags |= 128), yn(a), null);
          break;
        case 13:
          var l = a.memoizedState;
          if (l !== null)
            return l.dehydrated !== null
              ? (xt(a), (a.flags |= 128), null)
              : (t & a.child.childLanes) !== 0
                ? Fp(e, a, t)
                : (xt(a), (e = dt(e, a, t)), e !== null ? e.sibling : null);
          xt(a);
          break;
        case 19:
          var u = (e.flags & 128) !== 0;
          if (
            ((l = (t & a.childLanes) !== 0),
            l || (Cu(e, a, t, !1), (l = (t & a.childLanes) !== 0)),
            u)
          ) {
            if (l) return Pp(e, a, t);
            a.flags |= 128;
          }
          if (
            ((u = a.memoizedState),
            u !== null &&
              ((u.rendering = null), (u.tail = null), (u.lastEffect = null)),
            te(me, me.current),
            l)
          )
            break;
          return null;
        case 22:
          return ((a.lanes = 0), Hp(e, a, t, a.pendingProps));
        case 24:
          gt(a, ge, e.memoizedState.cache);
      }
      return dt(e, a, t);
    }
    function _p(e, a, t) {
      if (e !== null)
        if (e.memoizedProps !== a.pendingProps) xe = !0;
        else {
          if (!ki(e, t) && (a.flags & 128) === 0)
            return ((xe = !1), LC(e, a, t));
          xe = (e.flags & 131072) !== 0;
        }
      else ((xe = !1), Y && (a.flags & 1048576) !== 0 && Xm(a, mo, a.index));
      switch (((a.lanes = 0), a.tag)) {
        case 16:
          e: {
            var l = a.pendingProps;
            if (((e = sl(a.elementType)), (a.type = e), typeof e == "function"))
              ui(e)
                ? ((l = xl(e, l)), (a.tag = 1), (a = Ic(null, a, e, l, t)))
                : ((a.tag = 0), (a = kn(null, a, e, l, t)));
            else {
              if (e != null) {
                var u = e.$$typeof;
                if (u === Vn) {
                  ((a.tag = 11), (a = xc(null, a, e, l, t)));
                  break e;
                } else if (u === Xn) {
                  ((a.tag = 14), (a = Cc(null, a, e, l, t)));
                  break e;
                }
              }
              throw ((a = Wf(e) || e), Error(v(306, a, "")));
            }
          }
          return a;
        case 0:
          return kn(e, a, a.type, a.pendingProps, t);
        case 1:
          return ((l = a.type), (u = xl(l, a.pendingProps)), Ic(e, a, l, u, t));
        case 3:
          e: {
            if ((Xd(a, a.stateNode.containerInfo), e === null))
              throw Error(v(387));
            l = a.pendingProps;
            var o = a.memoizedState;
            ((u = o.element), gn(e, a), ao(a, l, null, t));
            var d = a.memoizedState;
            if (
              ((l = d.cache),
              gt(a, ge, l),
              l !== o.cache && hn(a, [ge], t, !0),
              eo(),
              (l = d.element),
              o.isDehydrated)
            )
              if (
                ((o = { element: l, isDehydrated: !1, cache: d.cache }),
                (a.updateQueue.baseState = o),
                (a.memoizedState = o),
                a.flags & 256)
              ) {
                a = bc(e, a, l, t);
                break e;
              } else if (l !== u) {
                ((u = pa(Error(v(424)), a)), po(u), (a = bc(e, a, l, t)));
                break e;
              } else
                for (
                  e = a.stateNode.containerInfo,
                    e.nodeType === 9
                      ? (e = e.body)
                      : (e = e.nodeName === "HTML" ? e.ownerDocument.body : e),
                    oe = ga(e.firstChild),
                    ke = a,
                    Y = !0,
                    Mt = null,
                    ha = !0,
                    t = Jm(a, null, l, t),
                    a.child = t;
                  t;
                )
                  ((t.flags = (t.flags & -3) | 4096), (t = t.sibling));
            else {
              if ((pl(), l === u)) {
                a = dt(e, a, t);
                break e;
              }
              be(e, a, l, t);
            }
            a = a.child;
          }
          return a;
        case 26:
          return (
            Ud(e, a),
            e === null
              ? (t = jc(a.type, null, a.pendingProps, null))
                ? (a.memoizedState = t)
                : Y ||
                  ((t = a.type),
                  (e = a.pendingProps),
                  (l = ms(kt.current).createElement(t)),
                  (l[Ae] = a),
                  (l[Ge] = e),
                  we(l, t, e),
                  ve(l),
                  (a.stateNode = l))
              : (a.memoizedState = jc(
                  a.type,
                  e.memoizedProps,
                  a.pendingProps,
                  e.memoizedState,
                )),
            null
          );
        case 27:
          return (
            en(a),
            e === null &&
              Y &&
              ((l = a.stateNode = zh(a.type, a.pendingProps, kt.current)),
              (ke = a),
              (ha = !0),
              (u = oe),
              Ft(a.type) ? ((Pn = u), (oe = ga(l.firstChild))) : (oe = u)),
            be(e, a, a.pendingProps.children, t),
            Ud(e, a),
            e === null && (a.flags |= 4194304),
            a.child
          );
        case 5:
          return (
            e === null &&
              Y &&
              ((u = l = oe) &&
                ((l = VC(l, a.type, a.pendingProps, ha)),
                l !== null
                  ? ((a.stateNode = l),
                    (ke = a),
                    (oe = ga(l.firstChild)),
                    (ha = !1),
                    (u = !0))
                  : (u = !1)),
              u || qt(a)),
            en(a),
            (u = a.type),
            (o = a.pendingProps),
            (d = e !== null ? e.memoizedProps : null),
            (l = o.children),
            Nn(u, o) ? (l = null) : d !== null && Nn(u, d) && (a.flags |= 32),
            a.memoizedState !== null &&
              ((u = mi(e, a, sC, null, null, t)), (yo._currentValue = u)),
            Ud(e, a),
            be(e, a, l, t),
            a.child
          );
        case 6:
          return (
            e === null &&
              Y &&
              ((e = t = oe) &&
                ((t = XC(t, a.pendingProps, ha)),
                t !== null
                  ? ((a.stateNode = t), (ke = a), (oe = null), (e = !0))
                  : (e = !1)),
              e || qt(a)),
            null
          );
        case 13:
          return Fp(e, a, t);
        case 4:
          return (
            Xd(a, a.stateNode.containerInfo),
            (l = a.pendingProps),
            e === null ? (a.child = Ll(a, null, l, t)) : be(e, a, l, t),
            a.child
          );
        case 11:
          return xc(e, a, a.type, a.pendingProps, t);
        case 7:
          return (be(e, a, a.pendingProps, t), a.child);
        case 8:
          return (be(e, a, a.pendingProps.children, t), a.child);
        case 12:
          return (be(e, a, a.pendingProps.children, t), a.child);
        case 10:
          return (
            (l = a.pendingProps),
            gt(a, a.type, l.value),
            be(e, a, l.children, t),
            a.child
          );
        case 9:
          return (
            (u = a.type._context),
            (l = a.pendingProps.children),
            hl(a),
            (u = Me(u)),
            (l = l(u)),
            (a.flags |= 1),
            be(e, a, l, t),
            a.child
          );
        case 14:
          return Cc(e, a, a.type, a.pendingProps, t);
        case 15:
          return Up(e, a, a.type, a.pendingProps, t);
        case 19:
          return Pp(e, a, t);
        case 31:
          return hC(e, a, t);
        case 22:
          return Hp(e, a, t, a.pendingProps);
        case 24:
          return (
            hl(a),
            (l = Me(ge)),
            e === null
              ? ((u = fi()),
                u === null &&
                  ((u = ae),
                  (o = si()),
                  (u.pooledCache = o),
                  o.refCount++,
                  o !== null && (u.pooledCacheLanes |= t),
                  (u = o)),
                (a.memoizedState = { parent: l, cache: u }),
                ii(a),
                gt(a, ge, u))
              : ((e.lanes & t) !== 0 && (gn(e, a), ao(a, null, null, t), eo()),
                (u = e.memoizedState),
                (o = a.memoizedState),
                u.parent !== l
                  ? ((u = { parent: l, cache: l }),
                    (a.memoizedState = u),
                    a.lanes === 0 &&
                      (a.memoizedState = a.updateQueue.baseState = u),
                    gt(a, ge, l))
                  : ((l = o.cache),
                    gt(a, ge, l),
                    l !== u.cache && hn(a, [ge], t, !0))),
            be(e, a, a.pendingProps.children, t),
            a.child
          );
        case 29:
          throw a.pendingProps;
      }
      throw Error(v(156, a.tag));
    }
    function Xa(e) {
      e.flags |= 4;
    }
    function Nf(e, a, t, l, u) {
      if (((a = (e.mode & 32) !== 0) && (a = !1), a)) {
        if (((e.flags |= 16777216), (u & 335544128) === u))
          if (e.stateNode.complete) e.flags |= 8192;
          else if (ch()) e.flags |= 8192;
          else throw ((cl = $d), ni);
      } else e.flags &= -16777217;
    }
    function kc(e, a) {
      if (a.type !== "stylesheet" || (a.state.loading & 4) !== 0)
        e.flags &= -16777217;
      else if (((e.flags |= 16777216), !Eh(a)))
        if (ch()) e.flags |= 8192;
        else throw ((cl = $d), ni);
    }
    function vd(e, a) {
      (a !== null && (e.flags |= 4),
        e.flags & 16384 &&
          ((a = e.tag !== 22 ? mm() : 536870912), (e.lanes |= a), (cu |= a)));
    }
    function Fu(e, a) {
      if (!Y)
        switch (e.tailMode) {
          case "hidden":
            a = e.tail;
            for (var t = null; a !== null; )
              (a.alternate !== null && (t = a), (a = a.sibling));
            t === null ? (e.tail = null) : (t.sibling = null);
            break;
          case "collapsed":
            t = e.tail;
            for (var l = null; t !== null; )
              (t.alternate !== null && (l = t), (t = t.sibling));
            l === null
              ? a || e.tail === null
                ? (e.tail = null)
                : (e.tail.sibling = null)
              : (l.sibling = null);
        }
    }
    function ue(e) {
      var a = e.alternate !== null && e.alternate.child === e.child,
        t = 0,
        l = 0;
      if (a)
        for (var u = e.child; u !== null; )
          ((t |= u.lanes | u.childLanes),
            (l |= u.subtreeFlags & 65011712),
            (l |= u.flags & 65011712),
            (u.return = e),
            (u = u.sibling));
      else
        for (u = e.child; u !== null; )
          ((t |= u.lanes | u.childLanes),
            (l |= u.subtreeFlags),
            (l |= u.flags),
            (u.return = e),
            (u = u.sibling));
      return ((e.subtreeFlags |= l), (e.childLanes = t), a);
    }
    function gC(e, a, t) {
      var l = a.pendingProps;
      switch ((di(a), a.tag)) {
        case 16:
        case 15:
        case 0:
        case 11:
        case 7:
        case 8:
        case 12:
        case 9:
        case 14:
          return (ue(a), null);
        case 1:
          return (ue(a), null);
        case 3:
          return (
            (t = a.stateNode),
            (l = null),
            e !== null && (l = e.memoizedState.cache),
            a.memoizedState.cache !== l && (a.flags |= 2048),
            at(ge),
            du(),
            t.pendingContext &&
              ((t.context = t.pendingContext), (t.pendingContext = null)),
            (e === null || e.child === null) &&
              (Nl(a)
                ? Xa(a)
                : e === null ||
                  (e.memoizedState.isDehydrated && (a.flags & 256) === 0) ||
                  ((a.flags |= 1024), wf())),
            ue(a),
            null
          );
        case 26:
          var u = a.type,
            o = a.memoizedState;
          return (
            e === null
              ? (Xa(a),
                o !== null ? (ue(a), kc(a, o)) : (ue(a), Nf(a, u, null, l, t)))
              : o
                ? o !== e.memoizedState
                  ? (Xa(a), ue(a), kc(a, o))
                  : (ue(a), (a.flags &= -16777217))
                : ((e = e.memoizedProps),
                  e !== l && Xa(a),
                  ue(a),
                  Nf(a, u, e, l, t)),
            null
          );
        case 27:
          if (
            (Yd(a),
            (t = kt.current),
            (u = a.type),
            e !== null && a.stateNode != null)
          )
            e.memoizedProps !== l && Xa(a);
          else {
            if (!l) {
              if (a.stateNode === null) throw Error(v(166));
              return (ue(a), null);
            }
            ((e = Oa.current),
              Nl(a) ? ac(a, e) : ((e = zh(u, l, t)), (a.stateNode = e), Xa(a)));
          }
          return (ue(a), null);
        case 5:
          if ((Yd(a), (u = a.type), e !== null && a.stateNode != null))
            e.memoizedProps !== l && Xa(a);
          else {
            if (!l) {
              if (a.stateNode === null) throw Error(v(166));
              return (ue(a), null);
            }
            if (((o = Oa.current), Nl(a))) ac(a, o);
            else {
              var d = ms(kt.current);
              switch (o) {
                case 1:
                  o = d.createElementNS("http://www.w3.org/2000/svg", u);
                  break;
                case 2:
                  o = d.createElementNS(
                    "http://www.w3.org/1998/Math/MathML",
                    u,
                  );
                  break;
                default:
                  switch (u) {
                    case "svg":
                      o = d.createElementNS("http://www.w3.org/2000/svg", u);
                      break;
                    case "math":
                      o = d.createElementNS(
                        "http://www.w3.org/1998/Math/MathML",
                        u,
                      );
                      break;
                    case "script":
                      ((o = d.createElement("div")),
                        (o.innerHTML = "<script><\/script>"),
                        (o = o.removeChild(o.firstChild)));
                      break;
                    case "select":
                      ((o =
                        typeof l.is == "string"
                          ? d.createElement("select", { is: l.is })
                          : d.createElement("select")),
                        l.multiple
                          ? (o.multiple = !0)
                          : l.size && (o.size = l.size));
                      break;
                    default:
                      o =
                        typeof l.is == "string"
                          ? d.createElement(u, { is: l.is })
                          : d.createElement(u);
                  }
              }
              ((o[Ae] = a), (o[Ge] = l));
              e: for (d = a.child; d !== null; ) {
                if (d.tag === 5 || d.tag === 6) o.appendChild(d.stateNode);
                else if (d.tag !== 4 && d.tag !== 27 && d.child !== null) {
                  ((d.child.return = d), (d = d.child));
                  continue;
                }
                if (d === a) break e;
                for (; d.sibling === null; ) {
                  if (d.return === null || d.return === a) break e;
                  d = d.return;
                }
                ((d.sibling.return = d.return), (d = d.sibling));
              }
              a.stateNode = o;
              e: switch ((we(o, u, l), u)) {
                case "button":
                case "input":
                case "select":
                case "textarea":
                  l = !!l.autoFocus;
                  break e;
                case "img":
                  l = !0;
                  break e;
                default:
                  l = !1;
              }
              l && Xa(a);
            }
          }
          return (
            ue(a),
            Nf(
              a,
              a.type,
              e === null ? null : e.memoizedProps,
              a.pendingProps,
              t,
            ),
            null
          );
        case 6:
          if (e && a.stateNode != null) e.memoizedProps !== l && Xa(a);
          else {
            if (typeof l != "string" && a.stateNode === null)
              throw Error(v(166));
            if (((e = kt.current), Nl(a))) {
              if (
                ((e = a.stateNode),
                (t = a.memoizedProps),
                (l = null),
                (u = ke),
                u !== null)
              )
                switch (u.tag) {
                  case 27:
                  case 5:
                    l = u.memoizedProps;
                }
              ((e[Ae] = a),
                (e = !!(
                  e.nodeValue === t ||
                  (l !== null && l.suppressHydrationWarning === !0) ||
                  wh(e.nodeValue, t)
                )),
                e || qt(a, !0));
            } else
              ((e = ms(e).createTextNode(l)), (e[Ae] = a), (a.stateNode = e));
          }
          return (ue(a), null);
        case 31:
          if (((t = a.memoizedState), e === null || e.memoizedState !== null)) {
            if (((l = Nl(a)), t !== null)) {
              if (e === null) {
                if (!l) throw Error(v(318));
                if (
                  ((e = a.memoizedState),
                  (e = e !== null ? e.dehydrated : null),
                  !e)
                )
                  throw Error(v(557));
                e[Ae] = a;
              } else
                (pl(),
                  (a.flags & 128) === 0 && (a.memoizedState = null),
                  (a.flags |= 4));
              (ue(a), (e = !1));
            } else
              ((t = wf()),
                e !== null &&
                  e.memoizedState !== null &&
                  (e.memoizedState.hydrationErrors = t),
                (e = !0));
            if (!e) return a.flags & 256 ? (Ze(a), a) : (Ze(a), null);
            if ((a.flags & 128) !== 0) throw Error(v(558));
          }
          return (ue(a), null);
        case 13:
          if (
            ((l = a.memoizedState),
            e === null ||
              (e.memoizedState !== null && e.memoizedState.dehydrated !== null))
          ) {
            if (((u = Nl(a)), l !== null && l.dehydrated !== null)) {
              if (e === null) {
                if (!u) throw Error(v(318));
                if (
                  ((u = a.memoizedState),
                  (u = u !== null ? u.dehydrated : null),
                  !u)
                )
                  throw Error(v(317));
                u[Ae] = a;
              } else
                (pl(),
                  (a.flags & 128) === 0 && (a.memoizedState = null),
                  (a.flags |= 4));
              (ue(a), (u = !1));
            } else
              ((u = wf()),
                e !== null &&
                  e.memoizedState !== null &&
                  (e.memoizedState.hydrationErrors = u),
                (u = !0));
            if (!u) return a.flags & 256 ? (Ze(a), a) : (Ze(a), null);
          }
          return (
            Ze(a),
            (a.flags & 128) !== 0
              ? ((a.lanes = t), a)
              : ((t = l !== null),
                (e = e !== null && e.memoizedState !== null),
                t &&
                  ((l = a.child),
                  (u = null),
                  l.alternate !== null &&
                    l.alternate.memoizedState !== null &&
                    l.alternate.memoizedState.cachePool !== null &&
                    (u = l.alternate.memoizedState.cachePool.pool),
                  (o = null),
                  l.memoizedState !== null &&
                    l.memoizedState.cachePool !== null &&
                    (o = l.memoizedState.cachePool.pool),
                  o !== u && (l.flags |= 2048)),
                t !== e && t && (a.child.flags |= 8192),
                vd(a, a.updateQueue),
                ue(a),
                null)
          );
        case 4:
          return (
            du(),
            e === null && Ri(a.stateNode.containerInfo),
            ue(a),
            null
          );
        case 10:
          return (at(a.type), ue(a), null);
        case 19:
          if ((Ie(me), (l = a.memoizedState), l === null)) return (ue(a), null);
          if (((u = (a.flags & 128) !== 0), (o = l.rendering), o === null))
            if (u) Fu(l, !1);
            else {
              if (re !== 0 || (e !== null && (e.flags & 128) !== 0))
                for (e = a.child; e !== null; ) {
                  if (((o = as(e)), o !== null)) {
                    for (
                      a.flags |= 128,
                        Fu(l, !1),
                        e = o.updateQueue,
                        a.updateQueue = e,
                        vd(a, e),
                        a.subtreeFlags = 0,
                        e = t,
                        t = a.child;
                      t !== null;
                    )
                      (Gm(t, e), (t = t.sibling));
                    return (
                      te(me, (me.current & 1) | 2),
                      Y && Qa(a, l.treeForkCount),
                      a.child
                    );
                  }
                  e = e.sibling;
                }
              l.tail !== null &&
                Je() > ss &&
                ((a.flags |= 128), (u = !0), Fu(l, !1), (a.lanes = 4194304));
            }
          else {
            if (!u)
              if (((e = as(o)), e !== null)) {
                if (
                  ((a.flags |= 128),
                  (u = !0),
                  (e = e.updateQueue),
                  (a.updateQueue = e),
                  vd(a, e),
                  Fu(l, !0),
                  l.tail === null &&
                    l.tailMode === "hidden" &&
                    !o.alternate &&
                    !Y)
                )
                  return (ue(a), null);
              } else
                2 * Je() - l.renderingStartTime > ss &&
                  t !== 536870912 &&
                  ((a.flags |= 128), (u = !0), Fu(l, !1), (a.lanes = 4194304));
            l.isBackwards
              ? ((o.sibling = a.child), (a.child = o))
              : ((e = l.last),
                e !== null ? (e.sibling = o) : (a.child = o),
                (l.last = o));
          }
          return l.tail !== null
            ? ((e = l.tail),
              (l.rendering = e),
              (l.tail = e.sibling),
              (l.renderingStartTime = Je()),
              (e.sibling = null),
              (t = me.current),
              te(me, u ? (t & 1) | 2 : t & 1),
              Y && Qa(a, l.treeForkCount),
              e)
            : (ue(a), null);
        case 22:
        case 23:
          return (
            Ze(a),
            ri(),
            (l = a.memoizedState !== null),
            e !== null
              ? (e.memoizedState !== null) !== l && (a.flags |= 8192)
              : l && (a.flags |= 8192),
            l
              ? (t & 536870912) !== 0 &&
                (a.flags & 128) === 0 &&
                (ue(a), a.subtreeFlags & 6 && (a.flags |= 8192))
              : ue(a),
            (t = a.updateQueue),
            t !== null && vd(a, t.retryQueue),
            (t = null),
            e !== null &&
              e.memoizedState !== null &&
              e.memoizedState.cachePool !== null &&
              (t = e.memoizedState.cachePool.pool),
            (l = null),
            a.memoizedState !== null &&
              a.memoizedState.cachePool !== null &&
              (l = a.memoizedState.cachePool.pool),
            l !== t && (a.flags |= 2048),
            e !== null && Ie(rl),
            null
          );
        case 24:
          return (
            (t = null),
            e !== null && (t = e.memoizedState.cache),
            a.memoizedState.cache !== t && (a.flags |= 2048),
            at(ge),
            ue(a),
            null
          );
        case 25:
          return null;
        case 30:
          return null;
      }
      throw Error(v(156, a.tag));
    }
    function xC(e, a) {
      switch ((di(a), a.tag)) {
        case 1:
          return (
            (e = a.flags),
            e & 65536 ? ((a.flags = (e & -65537) | 128), a) : null
          );
        case 3:
          return (
            at(ge),
            du(),
            (e = a.flags),
            (e & 65536) !== 0 && (e & 128) === 0
              ? ((a.flags = (e & -65537) | 128), a)
              : null
          );
        case 26:
        case 27:
        case 5:
          return (Yd(a), null);
        case 31:
          if (a.memoizedState !== null) {
            if ((Ze(a), a.alternate === null)) throw Error(v(340));
            pl();
          }
          return (
            (e = a.flags),
            e & 65536 ? ((a.flags = (e & -65537) | 128), a) : null
          );
        case 13:
          if (
            (Ze(a), (e = a.memoizedState), e !== null && e.dehydrated !== null)
          ) {
            if (a.alternate === null) throw Error(v(340));
            pl();
          }
          return (
            (e = a.flags),
            e & 65536 ? ((a.flags = (e & -65537) | 128), a) : null
          );
        case 19:
          return (Ie(me), null);
        case 4:
          return (du(), null);
        case 10:
          return (at(a.type), null);
        case 22:
        case 23:
          return (
            Ze(a),
            ri(),
            e !== null && Ie(rl),
            (e = a.flags),
            e & 65536 ? ((a.flags = (e & -65537) | 128), a) : null
          );
        case 24:
          return (at(ge), null);
        case 25:
          return null;
        default:
          return null;
      }
    }
    function Gp(e, a) {
      switch ((di(a), a.tag)) {
        case 3:
          (at(ge), du());
          break;
        case 26:
        case 27:
        case 5:
          Yd(a);
          break;
        case 4:
          du();
          break;
        case 31:
          a.memoizedState !== null && Ze(a);
          break;
        case 13:
          Ze(a);
          break;
        case 19:
          Ie(me);
          break;
        case 10:
          at(a.type);
          break;
        case 22:
        case 23:
          (Ze(a), ri(), e !== null && Ie(rl));
          break;
        case 24:
          at(ge);
      }
    }
    function Bo(e, a) {
      try {
        var t = a.updateQueue,
          l = t !== null ? t.lastEffect : null;
        if (l !== null) {
          var u = l.next;
          t = u;
          do {
            if ((t.tag & e) === e) {
              l = void 0;
              var o = t.create,
                d = t.inst;
              ((l = o()), (d.destroy = l));
            }
            t = t.next;
          } while (t !== u);
        }
      } catch (f) {
        W(a, a.return, f);
      }
    }
    function Nt(e, a, t) {
      try {
        var l = a.updateQueue,
          u = l !== null ? l.lastEffect : null;
        if (u !== null) {
          var o = u.next;
          l = o;
          do {
            if ((l.tag & e) === e) {
              var d = l.inst,
                f = d.destroy;
              if (f !== void 0) {
                ((d.destroy = void 0), (u = a));
                var n = t,
                  p = f;
                try {
                  p();
                } catch (h) {
                  W(u, n, h);
                }
              }
            }
            l = l.next;
          } while (l !== o);
        }
      } catch (h) {
        W(a, a.return, h);
      }
    }
    function Vp(e) {
      var a = e.updateQueue;
      if (a !== null) {
        var t = e.stateNode;
        try {
          $m(a, t);
        } catch (l) {
          W(e, e.return, l);
        }
      }
    }
    function Xp(e, a, t) {
      ((t.props = xl(e.type, e.memoizedProps)), (t.state = e.memoizedState));
      try {
        t.componentWillUnmount();
      } catch (l) {
        W(e, a, l);
      }
    }
    function lo(e, a) {
      try {
        var t = e.ref;
        if (t !== null) {
          switch (e.tag) {
            case 26:
            case 27:
            case 5:
              var l = e.stateNode;
              break;
            case 30:
              l = e.stateNode;
              break;
            default:
              l = e.stateNode;
          }
          typeof t == "function" ? (e.refCleanup = t(l)) : (t.current = l);
        }
      } catch (u) {
        W(e, a, u);
      }
    }
    function Ra(e, a) {
      var t = e.ref,
        l = e.refCleanup;
      if (t !== null)
        if (typeof l == "function")
          try {
            l();
          } catch (u) {
            W(e, a, u);
          } finally {
            ((e.refCleanup = null),
              (e = e.alternate),
              e != null && (e.refCleanup = null));
          }
        else if (typeof t == "function")
          try {
            t(null);
          } catch (u) {
            W(e, a, u);
          }
        else t.current = null;
    }
    function Yp(e) {
      var a = e.type,
        t = e.memoizedProps,
        l = e.stateNode;
      try {
        e: switch (a) {
          case "button":
          case "input":
          case "select":
          case "textarea":
            t.autoFocus && l.focus();
            break e;
          case "img":
            t.src ? (l.src = t.src) : t.srcSet && (l.srcset = t.srcSet);
        }
      } catch (u) {
        W(e, e.return, u);
      }
    }
    function Uf(e, a, t) {
      try {
        var l = e.stateNode;
        (UC(l, e.type, t, a), (l[Ge] = a));
      } catch (u) {
        W(e, e.return, u);
      }
    }
    function jp(e) {
      return (
        e.tag === 5 ||
        e.tag === 3 ||
        e.tag === 26 ||
        (e.tag === 27 && Ft(e.type)) ||
        e.tag === 4
      );
    }
    function Hf(e) {
      e: for (;;) {
        for (; e.sibling === null; ) {
          if (e.return === null || jp(e.return)) return null;
          e = e.return;
        }
        for (
          e.sibling.return = e.return, e = e.sibling;
          e.tag !== 5 && e.tag !== 6 && e.tag !== 18;
        ) {
          if (
            (e.tag === 27 && Ft(e.type)) ||
            e.flags & 2 ||
            e.child === null ||
            e.tag === 4
          )
            continue e;
          ((e.child.return = e), (e = e.child));
        }
        if (!(e.flags & 2)) return e.stateNode;
      }
    }
    function wn(e, a, t) {
      var l = e.tag;
      if (l === 5 || l === 6)
        ((e = e.stateNode),
          a
            ? (t.nodeType === 9
                ? t.body
                : t.nodeName === "HTML"
                  ? t.ownerDocument.body
                  : t
              ).insertBefore(e, a)
            : ((a =
                t.nodeType === 9
                  ? t.body
                  : t.nodeName === "HTML"
                    ? t.ownerDocument.body
                    : t),
              a.appendChild(e),
              (t = t._reactRootContainer),
              t != null || a.onclick !== null || (a.onclick = Wa)));
      else if (
        l !== 4 &&
        (l === 27 && Ft(e.type) && ((t = e.stateNode), (a = null)),
        (e = e.child),
        e !== null)
      )
        for (wn(e, a, t), e = e.sibling; e !== null; )
          (wn(e, a, t), (e = e.sibling));
    }
    function ds(e, a, t) {
      var l = e.tag;
      if (l === 5 || l === 6)
        ((e = e.stateNode), a ? t.insertBefore(e, a) : t.appendChild(e));
      else if (
        l !== 4 &&
        (l === 27 && Ft(e.type) && (t = e.stateNode), (e = e.child), e !== null)
      )
        for (ds(e, a, t), e = e.sibling; e !== null; )
          (ds(e, a, t), (e = e.sibling));
    }
    function Zp(e) {
      var a = e.stateNode,
        t = e.memoizedProps;
      try {
        for (var l = e.type, u = a.attributes; u.length; )
          a.removeAttributeNode(u[0]);
        (we(a, l, t), (a[Ae] = e), (a[Ge] = t));
      } catch (o) {
        W(e, e.return, o);
      }
    }
    var Ka = !1,
      Le = !1,
      Ff = !1,
      Mc = typeof WeakSet == "function" ? WeakSet : Set,
      Se = null;
    function CC(e, a) {
      if (((e = e.containerInfo), (En = gs), (e = Em(e)), ai(e))) {
        if ("selectionStart" in e)
          var t = { start: e.selectionStart, end: e.selectionEnd };
        else
          e: {
            t = ((t = e.ownerDocument) && t.defaultView) || window;
            var l = t.getSelection && t.getSelection();
            if (l && l.rangeCount !== 0) {
              t = l.anchorNode;
              var u = l.anchorOffset,
                o = l.focusNode;
              l = l.focusOffset;
              try {
                (t.nodeType, o.nodeType);
              } catch {
                t = null;
                break e;
              }
              var d = 0,
                f = -1,
                n = -1,
                p = 0,
                h = 0,
                x = e,
                m = null;
              a: for (;;) {
                for (
                  var L;
                  x !== t || (u !== 0 && x.nodeType !== 3) || (f = d + u),
                    x !== o || (l !== 0 && x.nodeType !== 3) || (n = d + l),
                    x.nodeType === 3 && (d += x.nodeValue.length),
                    (L = x.firstChild) !== null;
                )
                  ((m = x), (x = L));
                for (;;) {
                  if (x === e) break a;
                  if (
                    (m === t && ++p === u && (f = d),
                    m === o && ++h === l && (n = d),
                    (L = x.nextSibling) !== null)
                  )
                    break;
                  ((x = m), (m = x.parentNode));
                }
                x = L;
              }
              t = f === -1 || n === -1 ? null : { start: f, end: n };
            } else t = null;
          }
        t = t || { start: 0, end: 0 };
      } else t = null;
      for (
        qn = { focusedElem: e, selectionRange: t }, gs = !1, Se = a;
        Se !== null;
      )
        if (
          ((a = Se), (e = a.child), (a.subtreeFlags & 1028) !== 0 && e !== null)
        )
          ((e.return = a), (Se = e));
        else
          for (; Se !== null; ) {
            switch (((a = Se), (o = a.alternate), (e = a.flags), a.tag)) {
              case 0:
                if (
                  (e & 4) !== 0 &&
                  ((e = a.updateQueue),
                  (e = e !== null ? e.events : null),
                  e !== null)
                )
                  for (t = 0; t < e.length; t++)
                    ((u = e[t]), (u.ref.impl = u.nextImpl));
                break;
              case 11:
              case 15:
                break;
              case 1:
                if ((e & 1024) !== 0 && o !== null) {
                  ((e = void 0),
                    (t = a),
                    (u = o.memoizedProps),
                    (o = o.memoizedState),
                    (l = t.stateNode));
                  try {
                    var k = xl(t.type, u);
                    ((e = l.getSnapshotBeforeUpdate(k, o)),
                      (l.__reactInternalSnapshotBeforeUpdate = e));
                  } catch (w) {
                    W(t, t.return, w);
                  }
                }
                break;
              case 3:
                if ((e & 1024) !== 0) {
                  if (
                    ((e = a.stateNode.containerInfo), (t = e.nodeType), t === 9)
                  )
                    Un(e);
                  else if (t === 1)
                    switch (e.nodeName) {
                      case "HEAD":
                      case "HTML":
                      case "BODY":
                        Un(e);
                        break;
                      default:
                        e.textContent = "";
                    }
                }
                break;
              case 5:
              case 26:
              case 27:
              case 6:
              case 4:
              case 17:
                break;
              default:
                if ((e & 1024) !== 0) throw Error(v(163));
            }
            if (((e = a.sibling), e !== null)) {
              ((e.return = a.return), (Se = e));
              break;
            }
            Se = a.return;
          }
    }
    function Qp(e, a, t) {
      var l = t.flags;
      switch (t.tag) {
        case 0:
        case 11:
        case 15:
          (ja(e, t), l & 4 && Bo(5, t));
          break;
        case 1:
          if ((ja(e, t), l & 4))
            if (((e = t.stateNode), a === null))
              try {
                e.componentDidMount();
              } catch (d) {
                W(t, t.return, d);
              }
            else {
              var u = xl(t.type, a.memoizedProps);
              a = a.memoizedState;
              try {
                e.componentDidUpdate(
                  u,
                  a,
                  e.__reactInternalSnapshotBeforeUpdate,
                );
              } catch (d) {
                W(t, t.return, d);
              }
            }
          (l & 64 && Vp(t), l & 512 && lo(t, t.return));
          break;
        case 3:
          if ((ja(e, t), l & 64 && ((e = t.updateQueue), e !== null))) {
            if (((a = null), t.child !== null))
              switch (t.child.tag) {
                case 27:
                case 5:
                  a = t.child.stateNode;
                  break;
                case 1:
                  a = t.child.stateNode;
              }
            try {
              $m(e, a);
            } catch (d) {
              W(t, t.return, d);
            }
          }
          break;
        case 27:
          a === null && l & 4 && Zp(t);
        case 26:
        case 5:
          (ja(e, t), a === null && l & 4 && Yp(t), l & 512 && lo(t, t.return));
          break;
        case 12:
          ja(e, t);
          break;
        case 31:
          (ja(e, t), l & 4 && Wp(e, t));
          break;
        case 13:
          (ja(e, t),
            l & 4 && $p(e, t),
            l & 64 &&
              ((e = t.memoizedState),
              e !== null &&
                ((e = e.dehydrated),
                e !== null && ((t = wC.bind(null, t)), YC(e, t)))));
          break;
        case 22:
          if (((l = t.memoizedState !== null || Ka), !l)) {
            ((a = (a !== null && a.memoizedState !== null) || Le), (u = Ka));
            var o = Le;
            ((Ka = l),
              (Le = a) && !o
                ? Za(e, t, (t.subtreeFlags & 8772) !== 0)
                : ja(e, t),
              (Ka = u),
              (Le = o));
          }
          break;
        case 30:
          break;
        default:
          ja(e, t);
      }
    }
    function Kp(e) {
      var a = e.alternate;
      (a !== null && ((e.alternate = null), Kp(a)),
        (e.child = null),
        (e.deletions = null),
        (e.sibling = null),
        e.tag === 5 && ((a = e.stateNode), a !== null && Qn(a)),
        (e.stateNode = null),
        (e.return = null),
        (e.dependencies = null),
        (e.memoizedProps = null),
        (e.memoizedState = null),
        (e.pendingProps = null),
        (e.stateNode = null),
        (e.updateQueue = null));
    }
    var fe = null,
      Fe = !1;
    function Ya(e, a, t) {
      for (t = t.child; t !== null; ) (Jp(e, a, t), (t = t.sibling));
    }
    function Jp(e, a, t) {
      if (We && typeof We.onCommitFiberUnmount == "function")
        try {
          We.onCommitFiberUnmount(bo, t);
        } catch {}
      switch (t.tag) {
        case 26:
          (Le || Ra(t, a),
            Ya(e, a, t),
            t.memoizedState
              ? t.memoizedState.count--
              : t.stateNode &&
                ((t = t.stateNode), t.parentNode.removeChild(t)));
          break;
        case 27:
          Le || Ra(t, a);
          var l = fe,
            u = Fe;
          (Ft(t.type) && ((fe = t.stateNode), (Fe = !1)),
            Ya(e, a, t),
            fo(t.stateNode),
            (fe = l),
            (Fe = u));
          break;
        case 5:
          Le || Ra(t, a);
        case 6:
          if (
            ((l = fe),
            (u = Fe),
            (fe = null),
            Ya(e, a, t),
            (fe = l),
            (Fe = u),
            fe !== null)
          )
            if (Fe)
              try {
                (fe.nodeType === 9
                  ? fe.body
                  : fe.nodeName === "HTML"
                    ? fe.ownerDocument.body
                    : fe
                ).removeChild(t.stateNode);
              } catch (o) {
                W(t, a, o);
              }
            else
              try {
                fe.removeChild(t.stateNode);
              } catch (o) {
                W(t, a, o);
              }
          break;
        case 18:
          fe !== null &&
            (Fe
              ? ((e = fe),
                _c(
                  e.nodeType === 9
                    ? e.body
                    : e.nodeName === "HTML"
                      ? e.ownerDocument.body
                      : e,
                  t.stateNode,
                ),
                Lu(e))
              : _c(fe, t.stateNode));
          break;
        case 4:
          ((l = fe),
            (u = Fe),
            (fe = t.stateNode.containerInfo),
            (Fe = !0),
            Ya(e, a, t),
            (fe = l),
            (Fe = u));
          break;
        case 0:
        case 11:
        case 14:
        case 15:
          (Nt(2, t, a), Le || Nt(4, t, a), Ya(e, a, t));
          break;
        case 1:
          (Le ||
            (Ra(t, a),
            (l = t.stateNode),
            typeof l.componentWillUnmount == "function" && Xp(t, a, l)),
            Ya(e, a, t));
          break;
        case 21:
          Ya(e, a, t);
          break;
        case 22:
          ((Le = (l = Le) || t.memoizedState !== null), Ya(e, a, t), (Le = l));
          break;
        default:
          Ya(e, a, t);
      }
    }
    function Wp(e, a) {
      if (
        a.memoizedState === null &&
        ((e = a.alternate), e !== null && ((e = e.memoizedState), e !== null))
      ) {
        e = e.dehydrated;
        try {
          Lu(e);
        } catch (t) {
          W(a, a.return, t);
        }
      }
    }
    function $p(e, a) {
      if (
        a.memoizedState === null &&
        ((e = a.alternate),
        e !== null &&
          ((e = e.memoizedState),
          e !== null && ((e = e.dehydrated), e !== null)))
      )
        try {
          Lu(e);
        } catch (t) {
          W(a, a.return, t);
        }
    }
    function yC(e) {
      switch (e.tag) {
        case 31:
        case 13:
        case 19:
          var a = e.stateNode;
          return (a === null && (a = e.stateNode = new Mc()), a);
        case 22:
          return (
            (e = e.stateNode),
            (a = e._retryCache),
            a === null && (a = e._retryCache = new Mc()),
            a
          );
        default:
          throw Error(v(435, e.tag));
      }
    }
    function Id(e, a) {
      var t = yC(e);
      a.forEach(function (l) {
        if (!t.has(l)) {
          t.add(l);
          var u = DC.bind(null, e, l);
          l.then(u, u);
        }
      });
    }
    function Ue(e, a) {
      var t = a.deletions;
      if (t !== null)
        for (var l = 0; l < t.length; l++) {
          var u = t[l],
            o = e,
            d = a,
            f = d;
          e: for (; f !== null; ) {
            switch (f.tag) {
              case 27:
                if (Ft(f.type)) {
                  ((fe = f.stateNode), (Fe = !1));
                  break e;
                }
                break;
              case 5:
                ((fe = f.stateNode), (Fe = !1));
                break e;
              case 3:
              case 4:
                ((fe = f.stateNode.containerInfo), (Fe = !0));
                break e;
            }
            f = f.return;
          }
          if (fe === null) throw Error(v(160));
          (Jp(o, d, u),
            (fe = null),
            (Fe = !1),
            (o = u.alternate),
            o !== null && (o.return = null),
            (u.return = null));
        }
      if (a.subtreeFlags & 13886)
        for (a = a.child; a !== null; ) (eh(a, e), (a = a.sibling));
    }
    var va = null;
    function eh(e, a) {
      var t = e.alternate,
        l = e.flags;
      switch (e.tag) {
        case 0:
        case 11:
        case 14:
        case 15:
          (Ue(a, e),
            He(e),
            l & 4 && (Nt(3, e, e.return), Bo(3, e), Nt(5, e, e.return)));
          break;
        case 1:
          (Ue(a, e),
            He(e),
            l & 512 && (Le || t === null || Ra(t, t.return)),
            l & 64 &&
              Ka &&
              ((e = e.updateQueue),
              e !== null &&
                ((l = e.callbacks),
                l !== null &&
                  ((t = e.shared.hiddenCallbacks),
                  (e.shared.hiddenCallbacks = t === null ? l : t.concat(l))))));
          break;
        case 26:
          var u = va;
          if (
            (Ue(a, e),
            He(e),
            l & 512 && (Le || t === null || Ra(t, t.return)),
            l & 4)
          ) {
            var o = t !== null ? t.memoizedState : null;
            if (((l = e.memoizedState), t === null))
              if (l === null)
                if (e.stateNode === null) {
                  e: {
                    ((l = e.type),
                      (t = e.memoizedProps),
                      (u = u.ownerDocument || u));
                    a: switch (l) {
                      case "title":
                        ((o = u.getElementsByTagName("title")[0]),
                          (!o ||
                            o[Mo] ||
                            o[Ae] ||
                            o.namespaceURI === "http://www.w3.org/2000/svg" ||
                            o.hasAttribute("itemprop")) &&
                            ((o = u.createElement(l)),
                            u.head.insertBefore(
                              o,
                              u.querySelector("head > title"),
                            )),
                          we(o, l, t),
                          (o[Ae] = e),
                          ve(o),
                          (l = o));
                        break e;
                      case "link":
                        var d = Qc("link", "href", u).get(l + (t.href || ""));
                        if (d) {
                          for (var f = 0; f < d.length; f++)
                            if (
                              ((o = d[f]),
                              o.getAttribute("href") ===
                                (t.href == null || t.href === ""
                                  ? null
                                  : t.href) &&
                                o.getAttribute("rel") ===
                                  (t.rel == null ? null : t.rel) &&
                                o.getAttribute("title") ===
                                  (t.title == null ? null : t.title) &&
                                o.getAttribute("crossorigin") ===
                                  (t.crossOrigin == null
                                    ? null
                                    : t.crossOrigin))
                            ) {
                              d.splice(f, 1);
                              break a;
                            }
                        }
                        ((o = u.createElement(l)),
                          we(o, l, t),
                          u.head.appendChild(o));
                        break;
                      case "meta":
                        if (
                          (d = Qc("meta", "content", u).get(
                            l + (t.content || ""),
                          ))
                        ) {
                          for (f = 0; f < d.length; f++)
                            if (
                              ((o = d[f]),
                              o.getAttribute("content") ===
                                (t.content == null ? null : "" + t.content) &&
                                o.getAttribute("name") ===
                                  (t.name == null ? null : t.name) &&
                                o.getAttribute("property") ===
                                  (t.property == null ? null : t.property) &&
                                o.getAttribute("http-equiv") ===
                                  (t.httpEquiv == null ? null : t.httpEquiv) &&
                                o.getAttribute("charset") ===
                                  (t.charSet == null ? null : t.charSet))
                            ) {
                              d.splice(f, 1);
                              break a;
                            }
                        }
                        ((o = u.createElement(l)),
                          we(o, l, t),
                          u.head.appendChild(o));
                        break;
                      default:
                        throw Error(v(468, l));
                    }
                    ((o[Ae] = e), ve(o), (l = o));
                  }
                  e.stateNode = l;
                } else Kc(u, e.type, e.stateNode);
              else e.stateNode = Zc(u, l, e.memoizedProps);
            else
              o !== l
                ? (o === null
                    ? t.stateNode !== null &&
                      ((t = t.stateNode), t.parentNode.removeChild(t))
                    : o.count--,
                  l === null
                    ? Kc(u, e.type, e.stateNode)
                    : Zc(u, l, e.memoizedProps))
                : l === null &&
                  e.stateNode !== null &&
                  Uf(e, e.memoizedProps, t.memoizedProps);
          }
          break;
        case 27:
          (Ue(a, e),
            He(e),
            l & 512 && (Le || t === null || Ra(t, t.return)),
            t !== null && l & 4 && Uf(e, e.memoizedProps, t.memoizedProps));
          break;
        case 5:
          if (
            (Ue(a, e),
            He(e),
            l & 512 && (Le || t === null || Ra(t, t.return)),
            e.flags & 32)
          ) {
            u = e.stateNode;
            try {
              fu(u, "");
            } catch (k) {
              W(e, e.return, k);
            }
          }
          (l & 4 &&
            e.stateNode != null &&
            ((u = e.memoizedProps), Uf(e, u, t !== null ? t.memoizedProps : u)),
            l & 1024 && (Ff = !0));
          break;
        case 6:
          if ((Ue(a, e), He(e), l & 4)) {
            if (e.stateNode === null) throw Error(v(162));
            ((l = e.memoizedProps), (t = e.stateNode));
            try {
              t.nodeValue = l;
            } catch (k) {
              W(e, e.return, k);
            }
          }
          break;
        case 3:
          if (
            ((Pd = null),
            (u = va),
            (va = ps(a.containerInfo)),
            Ue(a, e),
            (va = u),
            He(e),
            l & 4 && t !== null && t.memoizedState.isDehydrated)
          )
            try {
              Lu(a.containerInfo);
            } catch (k) {
              W(e, e.return, k);
            }
          Ff && ((Ff = !1), ah(e));
          break;
        case 4:
          ((l = va),
            (va = ps(e.stateNode.containerInfo)),
            Ue(a, e),
            He(e),
            (va = l));
          break;
        case 12:
          (Ue(a, e), He(e));
          break;
        case 31:
          (Ue(a, e),
            He(e),
            l & 4 &&
              ((l = e.updateQueue),
              l !== null && ((e.updateQueue = null), Id(e, l))));
          break;
        case 13:
          (Ue(a, e),
            He(e),
            e.child.flags & 8192 &&
              (e.memoizedState !== null) !=
                (t !== null && t.memoizedState !== null) &&
              (Ts = Je()),
            l & 4 &&
              ((l = e.updateQueue),
              l !== null && ((e.updateQueue = null), Id(e, l))));
          break;
        case 22:
          u = e.memoizedState !== null;
          var n = t !== null && t.memoizedState !== null,
            p = Ka,
            h = Le;
          if (
            ((Ka = p || u),
            (Le = h || n),
            Ue(a, e),
            (Le = h),
            (Ka = p),
            He(e),
            l & 8192)
          )
            e: for (
              a = e.stateNode,
                a._visibility = u ? a._visibility & -2 : a._visibility | 1,
                u && (t === null || n || Ka || Le || fl(e)),
                t = null,
                a = e;
              ;
            ) {
              if (a.tag === 5 || a.tag === 26) {
                if (t === null) {
                  n = t = a;
                  try {
                    if (((o = n.stateNode), u))
                      ((d = o.style),
                        typeof d.setProperty == "function"
                          ? d.setProperty("display", "none", "important")
                          : (d.display = "none"));
                    else {
                      f = n.stateNode;
                      var x = n.memoizedProps.style,
                        m =
                          x != null && x.hasOwnProperty("display")
                            ? x.display
                            : null;
                      f.style.display =
                        m == null || typeof m == "boolean"
                          ? ""
                          : ("" + m).trim();
                    }
                  } catch (k) {
                    W(n, n.return, k);
                  }
                }
              } else if (a.tag === 6) {
                if (t === null) {
                  n = a;
                  try {
                    n.stateNode.nodeValue = u ? "" : n.memoizedProps;
                  } catch (k) {
                    W(n, n.return, k);
                  }
                }
              } else if (a.tag === 18) {
                if (t === null) {
                  n = a;
                  try {
                    var L = n.stateNode;
                    u ? Gc(L, !0) : Gc(n.stateNode, !1);
                  } catch (k) {
                    W(n, n.return, k);
                  }
                }
              } else if (
                ((a.tag !== 22 && a.tag !== 23) ||
                  a.memoizedState === null ||
                  a === e) &&
                a.child !== null
              ) {
                ((a.child.return = a), (a = a.child));
                continue;
              }
              if (a === e) break e;
              for (; a.sibling === null; ) {
                if (a.return === null || a.return === e) break e;
                (t === a && (t = null), (a = a.return));
              }
              (t === a && (t = null),
                (a.sibling.return = a.return),
                (a = a.sibling));
            }
          l & 4 &&
            ((l = e.updateQueue),
            l !== null &&
              ((t = l.retryQueue),
              t !== null && ((l.retryQueue = null), Id(e, t))));
          break;
        case 19:
          (Ue(a, e),
            He(e),
            l & 4 &&
              ((l = e.updateQueue),
              l !== null && ((e.updateQueue = null), Id(e, l))));
          break;
        case 30:
          break;
        case 21:
          break;
        default:
          (Ue(a, e), He(e));
      }
    }
    function He(e) {
      var a = e.flags;
      if (a & 2) {
        try {
          for (var t, l = e.return; l !== null; ) {
            if (jp(l)) {
              t = l;
              break;
            }
            l = l.return;
          }
          if (t == null) throw Error(v(160));
          switch (t.tag) {
            case 27:
              var u = t.stateNode,
                o = Hf(e);
              ds(e, o, u);
              break;
            case 5:
              var d = t.stateNode;
              t.flags & 32 && (fu(d, ""), (t.flags &= -33));
              var f = Hf(e);
              ds(e, f, d);
              break;
            case 3:
            case 4:
              var n = t.stateNode.containerInfo,
                p = Hf(e);
              wn(e, p, n);
              break;
            default:
              throw Error(v(161));
          }
        } catch (h) {
          W(e, e.return, h);
        }
        e.flags &= -3;
      }
      a & 4096 && (e.flags &= -4097);
    }
    function ah(e) {
      if (e.subtreeFlags & 1024)
        for (e = e.child; e !== null; ) {
          var a = e;
          (ah(a),
            a.tag === 5 && a.flags & 1024 && a.stateNode.reset(),
            (e = e.sibling));
        }
    }
    function ja(e, a) {
      if (a.subtreeFlags & 8772)
        for (a = a.child; a !== null; )
          (Qp(e, a.alternate, a), (a = a.sibling));
    }
    function fl(e) {
      for (e = e.child; e !== null; ) {
        var a = e;
        switch (a.tag) {
          case 0:
          case 11:
          case 14:
          case 15:
            (Nt(4, a, a.return), fl(a));
            break;
          case 1:
            Ra(a, a.return);
            var t = a.stateNode;
            (typeof t.componentWillUnmount == "function" && Xp(a, a.return, t),
              fl(a));
            break;
          case 27:
            fo(a.stateNode);
          case 26:
          case 5:
            (Ra(a, a.return), fl(a));
            break;
          case 22:
            a.memoizedState === null && fl(a);
            break;
          case 30:
            fl(a);
            break;
          default:
            fl(a);
        }
        e = e.sibling;
      }
    }
    function Za(e, a, t) {
      for (t = t && (a.subtreeFlags & 8772) !== 0, a = a.child; a !== null; ) {
        var l = a.alternate,
          u = e,
          o = a,
          d = o.flags;
        switch (o.tag) {
          case 0:
          case 11:
          case 15:
            (Za(u, o, t), Bo(4, o));
            break;
          case 1:
            if (
              (Za(u, o, t),
              (l = o),
              (u = l.stateNode),
              typeof u.componentDidMount == "function")
            )
              try {
                u.componentDidMount();
              } catch (p) {
                W(l, l.return, p);
              }
            if (((l = o), (u = l.updateQueue), u !== null)) {
              var f = l.stateNode;
              try {
                var n = u.shared.hiddenCallbacks;
                if (n !== null)
                  for (
                    u.shared.hiddenCallbacks = null, u = 0;
                    u < n.length;
                    u++
                  )
                    Wm(n[u], f);
              } catch (p) {
                W(l, l.return, p);
              }
            }
            (t && d & 64 && Vp(o), lo(o, o.return));
            break;
          case 27:
            Zp(o);
          case 26:
          case 5:
            (Za(u, o, t), t && l === null && d & 4 && Yp(o), lo(o, o.return));
            break;
          case 12:
            Za(u, o, t);
            break;
          case 31:
            (Za(u, o, t), t && d & 4 && Wp(u, o));
            break;
          case 13:
            (Za(u, o, t), t && d & 4 && $p(u, o));
            break;
          case 22:
            (o.memoizedState === null && Za(u, o, t), lo(o, o.return));
            break;
          case 30:
            break;
          default:
            Za(u, o, t);
        }
        a = a.sibling;
      }
    }
    function Mi(e, a) {
      var t = null;
      (e !== null &&
        e.memoizedState !== null &&
        e.memoizedState.cachePool !== null &&
        (t = e.memoizedState.cachePool.pool),
        (e = null),
        a.memoizedState !== null &&
          a.memoizedState.cachePool !== null &&
          (e = a.memoizedState.cachePool.pool),
        e !== t && (e != null && e.refCount++, t != null && Do(t)));
    }
    function wi(e, a) {
      ((e = null),
        a.alternate !== null && (e = a.alternate.memoizedState.cache),
        (a = a.memoizedState.cache),
        a !== e && (a.refCount++, e != null && Do(e)));
    }
    function Sa(e, a, t, l) {
      if (a.subtreeFlags & 10256)
        for (a = a.child; a !== null; ) (th(e, a, t, l), (a = a.sibling));
    }
    function th(e, a, t, l) {
      var u = a.flags;
      switch (a.tag) {
        case 0:
        case 11:
        case 15:
          (Sa(e, a, t, l), u & 2048 && Bo(9, a));
          break;
        case 1:
          Sa(e, a, t, l);
          break;
        case 3:
          (Sa(e, a, t, l),
            u & 2048 &&
              ((e = null),
              a.alternate !== null && (e = a.alternate.memoizedState.cache),
              (a = a.memoizedState.cache),
              a !== e && (a.refCount++, e != null && Do(e))));
          break;
        case 12:
          if (u & 2048) {
            (Sa(e, a, t, l), (e = a.stateNode));
            try {
              var o = a.memoizedProps,
                d = o.id,
                f = o.onPostCommit;
              typeof f == "function" &&
                f(
                  d,
                  a.alternate === null ? "mount" : "update",
                  e.passiveEffectDuration,
                  -0,
                );
            } catch (n) {
              W(a, a.return, n);
            }
          } else Sa(e, a, t, l);
          break;
        case 31:
          Sa(e, a, t, l);
          break;
        case 13:
          Sa(e, a, t, l);
          break;
        case 23:
          break;
        case 22:
          ((o = a.stateNode),
            (d = a.alternate),
            a.memoizedState !== null
              ? o._visibility & 2
                ? Sa(e, a, t, l)
                : uo(e, a)
              : o._visibility & 2
                ? Sa(e, a, t, l)
                : ((o._visibility |= 2),
                  Hl(e, a, t, l, (a.subtreeFlags & 10256) !== 0 || !1)),
            u & 2048 && Mi(d, a));
          break;
        case 24:
          (Sa(e, a, t, l), u & 2048 && wi(a.alternate, a));
          break;
        default:
          Sa(e, a, t, l);
      }
    }
    function Hl(e, a, t, l, u) {
      for (
        u = u && ((a.subtreeFlags & 10256) !== 0 || !1), a = a.child;
        a !== null;
      ) {
        var o = e,
          d = a,
          f = t,
          n = l,
          p = d.flags;
        switch (d.tag) {
          case 0:
          case 11:
          case 15:
            (Hl(o, d, f, n, u), Bo(8, d));
            break;
          case 23:
            break;
          case 22:
            var h = d.stateNode;
            (d.memoizedState !== null
              ? h._visibility & 2
                ? Hl(o, d, f, n, u)
                : uo(o, d)
              : ((h._visibility |= 2), Hl(o, d, f, n, u)),
              u && p & 2048 && Mi(d.alternate, d));
            break;
          case 24:
            (Hl(o, d, f, n, u), u && p & 2048 && wi(d.alternate, d));
            break;
          default:
            Hl(o, d, f, n, u);
        }
        a = a.sibling;
      }
    }
    function uo(e, a) {
      if (a.subtreeFlags & 10256)
        for (a = a.child; a !== null; ) {
          var t = e,
            l = a,
            u = l.flags;
          switch (l.tag) {
            case 22:
              (uo(t, l), u & 2048 && Mi(l.alternate, l));
              break;
            case 24:
              (uo(t, l), u & 2048 && wi(l.alternate, l));
              break;
            default:
              uo(t, l);
          }
          a = a.sibling;
        }
    }
    var Zu = 8192;
    function Ul(e, a, t) {
      if (e.subtreeFlags & Zu)
        for (e = e.child; e !== null; ) (lh(e, a, t), (e = e.sibling));
    }
    function lh(e, a, t) {
      switch (e.tag) {
        case 26:
          (Ul(e, a, t),
            e.flags & Zu &&
              e.memoizedState !== null &&
              uy(t, va, e.memoizedState, e.memoizedProps));
          break;
        case 5:
          Ul(e, a, t);
          break;
        case 3:
        case 4:
          var l = va;
          ((va = ps(e.stateNode.containerInfo)), Ul(e, a, t), (va = l));
          break;
        case 22:
          e.memoizedState === null &&
            ((l = e.alternate),
            l !== null && l.memoizedState !== null
              ? ((l = Zu), (Zu = 16777216), Ul(e, a, t), (Zu = l))
              : Ul(e, a, t));
          break;
        default:
          Ul(e, a, t);
      }
    }
    function uh(e) {
      var a = e.alternate;
      if (a !== null && ((e = a.child), e !== null)) {
        a.child = null;
        do ((a = e.sibling), (e.sibling = null), (e = a));
        while (e !== null);
      }
    }
    function Pu(e) {
      var a = e.deletions;
      if ((e.flags & 16) !== 0) {
        if (a !== null)
          for (var t = 0; t < a.length; t++) {
            var l = a[t];
            ((Se = l), dh(l, e));
          }
        uh(e);
      }
      if (e.subtreeFlags & 10256)
        for (e = e.child; e !== null; ) (oh(e), (e = e.sibling));
    }
    function oh(e) {
      switch (e.tag) {
        case 0:
        case 11:
        case 15:
          (Pu(e), e.flags & 2048 && Nt(9, e, e.return));
          break;
        case 3:
          Pu(e);
          break;
        case 12:
          Pu(e);
          break;
        case 22:
          var a = e.stateNode;
          e.memoizedState !== null &&
          a._visibility & 2 &&
          (e.return === null || e.return.tag !== 13)
            ? ((a._visibility &= -3), Hd(e))
            : Pu(e);
          break;
        default:
          Pu(e);
      }
    }
    function Hd(e) {
      var a = e.deletions;
      if ((e.flags & 16) !== 0) {
        if (a !== null)
          for (var t = 0; t < a.length; t++) {
            var l = a[t];
            ((Se = l), dh(l, e));
          }
        uh(e);
      }
      for (e = e.child; e !== null; ) {
        switch (((a = e), a.tag)) {
          case 0:
          case 11:
          case 15:
            (Nt(8, a, a.return), Hd(a));
            break;
          case 22:
            ((t = a.stateNode),
              t._visibility & 2 && ((t._visibility &= -3), Hd(a)));
            break;
          default:
            Hd(a);
        }
        e = e.sibling;
      }
    }
    function dh(e, a) {
      for (; Se !== null; ) {
        var t = Se;
        switch (t.tag) {
          case 0:
          case 11:
          case 15:
            Nt(8, t, a);
            break;
          case 23:
          case 22:
            if (
              t.memoizedState !== null &&
              t.memoizedState.cachePool !== null
            ) {
              var l = t.memoizedState.cachePool.pool;
              l != null && l.refCount++;
            }
            break;
          case 24:
            Do(t.memoizedState.cache);
        }
        if (((l = t.child), l !== null)) ((l.return = t), (Se = l));
        else
          e: for (t = e; Se !== null; ) {
            l = Se;
            var u = l.sibling,
              o = l.return;
            if ((Kp(l), l === t)) {
              Se = null;
              break e;
            }
            if (u !== null) {
              ((u.return = o), (Se = u));
              break e;
            }
            Se = o;
          }
      }
    }
    var SC = {
        getCacheForType: function (e) {
          var a = Me(ge),
            t = a.data.get(e);
          return (t === void 0 && ((t = e()), a.data.set(e, t)), t);
        },
        cacheSignal: function () {
          return Me(ge).controller.signal;
        },
      },
      vC = typeof WeakMap == "function" ? WeakMap : Map,
      Q = 0,
      ae = null,
      G = null,
      X = 0,
      J = 0,
      je = null,
      It = !1,
      Su = !1,
      Di = !1,
      st = 0,
      re = 0,
      Ut = 0,
      ml = 0,
      Ti = 0,
      Ke = 0,
      cu = 0,
      oo = null,
      Pe = null,
      Dn = !1,
      Ts = 0,
      sh = 0,
      ss = 1 / 0,
      fs = null,
      Tt = null,
      Ce = 0,
      Bt = null,
      mu = null,
      tt = 0,
      Tn = 0,
      Bn = null,
      fh = null,
      so = 0,
      zn = null;
    function ea() {
      return (Q & 2) !== 0 && X !== 0 ? X & -X : R.T !== null ? zi() : gm();
    }
    function nh() {
      if (Ke === 0)
        if ((X & 536870912) === 0 || Y) {
          var e = md;
          ((md <<= 1), (md & 3932160) === 0 && (md = 262144), (Ke = e));
        } else Ke = 536870912;
      return ((e = ta.current), e !== null && (e.flags |= 32), Ke);
    }
    function _e(e, a, t) {
      (((e === ae && (J === 2 || J === 9)) || e.cancelPendingCommit !== null) &&
        (pu(e, 0), bt(e, X, Ke, !1)),
        ko(e, t),
        ((Q & 2) === 0 || e !== ae) &&
          (e === ae &&
            ((Q & 2) === 0 && (ml |= t), re === 4 && bt(e, X, Ke, !1)),
          qa(e)));
    }
    function ih(e, a, t) {
      if ((Q & 6) !== 0) throw Error(v(327));
      var l = (!t && (a & 127) === 0 && (a & e.expiredLanes) === 0) || Ao(e, a),
        u = l ? AC(e, a) : Pf(e, a, !0),
        o = l;
      do {
        if (u === 0) {
          Su && !l && bt(e, a, 0, !1);
          break;
        } else {
          if (((t = e.current.alternate), o && !IC(t))) {
            ((u = Pf(e, a, !1)), (o = !1));
            continue;
          }
          if (u === 2) {
            if (((o = a), e.errorRecoveryDisabledLanes & o)) var d = 0;
            else
              ((d = e.pendingLanes & -536870913),
                (d = d !== 0 ? d : d & 536870912 ? 536870912 : 0));
            if (d !== 0) {
              a = d;
              e: {
                var f = e;
                u = oo;
                var n = f.current.memoizedState.isDehydrated;
                if (
                  (n && (pu(f, d).flags |= 256), (d = Pf(f, d, !1)), d !== 2)
                ) {
                  if (Di && !n) {
                    ((f.errorRecoveryDisabledLanes |= o), (ml |= o), (u = 4));
                    break e;
                  }
                  ((o = Pe),
                    (Pe = u),
                    o !== null &&
                      (Pe === null ? (Pe = o) : Pe.push.apply(Pe, o)));
                }
                u = d;
              }
              if (((o = !1), u !== 2)) continue;
            }
          }
          if (u === 1) {
            (pu(e, 0), bt(e, a, 0, !0));
            break;
          }
          e: {
            switch (((l = e), (o = u), o)) {
              case 0:
              case 1:
                throw Error(v(345));
              case 4:
                if ((a & 4194048) !== a) break;
              case 6:
                bt(l, a, Ke, !It);
                break e;
              case 2:
                Pe = null;
                break;
              case 3:
              case 5:
                break;
              default:
                throw Error(v(329));
            }
            if ((a & 62914560) === a && ((u = Ts + 300 - Je()), 10 < u)) {
              if ((bt(l, a, Ke, !It), Cs(l, 0, !0) !== 0)) break e;
              ((tt = a),
                (l.timeoutHandle = Th(
                  wc.bind(
                    null,
                    l,
                    t,
                    Pe,
                    fs,
                    Dn,
                    a,
                    Ke,
                    ml,
                    cu,
                    It,
                    o,
                    "Throttled",
                    -0,
                    0,
                  ),
                  u,
                )));
              break e;
            }
            wc(l, t, Pe, fs, Dn, a, Ke, ml, cu, It, o, null, -0, 0);
          }
        }
        break;
      } while (!0);
      qa(e);
    }
    function wc(e, a, t, l, u, o, d, f, n, p, h, x, m, L) {
      if (
        ((e.timeoutHandle = -1),
        (x = a.subtreeFlags),
        x & 8192 || (x & 16785408) === 16785408)
      ) {
        ((x = {
          stylesheets: null,
          count: 0,
          imgCount: 0,
          imgBytes: 0,
          suspenseyImages: [],
          waitingForImages: !0,
          waitingForViewTransition: !1,
          unsuspend: Wa,
        }),
          lh(a, o, x));
        var k =
          (o & 62914560) === o
            ? Ts - Je()
            : (o & 4194048) === o
              ? sh - Je()
              : 0;
        if (((k = oy(x, k)), k !== null)) {
          ((tt = o),
            (e.cancelPendingCommit = k(
              Tc.bind(null, e, a, o, t, l, u, d, f, n, h, x, null, m, L),
            )),
            bt(e, o, d, !p));
          return;
        }
      }
      Tc(e, a, o, t, l, u, d, f, n);
    }
    function IC(e) {
      for (var a = e; ; ) {
        var t = a.tag;
        if (
          (t === 0 || t === 11 || t === 15) &&
          a.flags & 16384 &&
          ((t = a.updateQueue), t !== null && ((t = t.stores), t !== null))
        )
          for (var l = 0; l < t.length; l++) {
            var u = t[l],
              o = u.getSnapshot;
            u = u.value;
            try {
              if (!aa(o(), u)) return !1;
            } catch {
              return !1;
            }
          }
        if (((t = a.child), a.subtreeFlags & 16384 && t !== null))
          ((t.return = a), (a = t));
        else {
          if (a === e) break;
          for (; a.sibling === null; ) {
            if (a.return === null || a.return === e) return !0;
            a = a.return;
          }
          ((a.sibling.return = a.return), (a = a.sibling));
        }
      }
      return !0;
    }
    function bt(e, a, t, l) {
      ((a &= ~Ti),
        (a &= ~ml),
        (e.suspendedLanes |= a),
        (e.pingedLanes &= ~a),
        l && (e.warmLanes |= a),
        (l = e.expirationTimes));
      for (var u = a; 0 < u; ) {
        var o = 31 - $e(u),
          d = 1 << o;
        ((l[o] = -1), (u &= ~d));
      }
      t !== 0 && pm(e, t, a);
    }
    function Bs() {
      return (Q & 6) === 0 ? (zo(0, !1), !1) : !0;
    }
    function Bi() {
      if (G !== null) {
        if (J === 0) var e = G.return;
        else ((e = G), ($a = Il = null), Li(e), (lu = null), (ho = 0), (e = G));
        for (; e !== null; ) (Gp(e.alternate, e), (e = e.return));
        G = null;
      }
    }
    function pu(e, a) {
      var t = e.timeoutHandle;
      (t !== -1 && ((e.timeoutHandle = -1), PC(t)),
        (t = e.cancelPendingCommit),
        t !== null && ((e.cancelPendingCommit = null), t()),
        (tt = 0),
        Bi(),
        (ae = e),
        (G = t = et(e.current, null)),
        (X = a),
        (J = 0),
        (je = null),
        (It = !1),
        (Su = Ao(e, a)),
        (Di = !1),
        (cu = Ke = Ti = ml = Ut = re = 0),
        (Pe = oo = null),
        (Dn = !1),
        (a & 8) !== 0 && (a |= a & 32));
      var l = e.entangledLanes;
      if (l !== 0)
        for (e = e.entanglements, l &= a; 0 < l; ) {
          var u = 31 - $e(l),
            o = 1 << u;
          ((a |= e[u]), (l &= ~o));
        }
      return ((st = a), Is(), t);
    }
    function rh(e, a) {
      ((N = null),
        (R.H = go),
        a === yu || a === As
          ? ((a = dc()), (J = 3))
          : a === ni
            ? ((a = dc()), (J = 4))
            : (J =
                a === Ai
                  ? 8
                  : a !== null &&
                      typeof a == "object" &&
                      typeof a.then == "function"
                    ? 6
                    : 1),
        (je = a),
        G === null && ((re = 1), us(e, pa(a, e.current))));
    }
    function ch() {
      var e = ta.current;
      return e === null
        ? !0
        : (X & 4194048) === X
          ? La === null
          : (X & 62914560) === X || (X & 536870912) !== 0
            ? e === La
            : !1;
    }
    function mh() {
      var e = R.H;
      return ((R.H = go), e === null ? go : e);
    }
    function ph() {
      var e = R.A;
      return ((R.A = SC), e);
    }
    function ns() {
      ((re = 4),
        It || ((X & 4194048) !== X && ta.current !== null) || (Su = !0),
        ((Ut & 134217727) === 0 && (ml & 134217727) === 0) ||
          ae === null ||
          bt(ae, X, Ke, !1));
    }
    function Pf(e, a, t) {
      var l = Q;
      Q |= 2;
      var u = mh(),
        o = ph();
      ((ae !== e || X !== a) && ((fs = null), pu(e, a)), (a = !1));
      var d = re;
      e: do
        try {
          if (J !== 0 && G !== null) {
            var f = G,
              n = je;
            switch (J) {
              case 8:
                (Bi(), (d = 6));
                break e;
              case 3:
              case 2:
              case 9:
              case 6:
                ta.current === null && (a = !0);
                var p = J;
                if (((J = 0), (je = null), Wl(e, f, n, p), t && Su)) {
                  d = 0;
                  break e;
                }
                break;
              default:
                ((p = J), (J = 0), (je = null), Wl(e, f, n, p));
            }
          }
          (bC(), (d = re));
          break;
        } catch (h) {
          rh(e, h);
        }
      while (!0);
      return (
        a && e.shellSuspendCounter++,
        ($a = Il = null),
        (Q = l),
        (R.H = u),
        (R.A = o),
        G === null && ((ae = null), (X = 0), Is()),
        d
      );
    }
    function bC() {
      for (; G !== null; ) hh(G);
    }
    function AC(e, a) {
      var t = Q;
      Q |= 2;
      var l = mh(),
        u = ph();
      ae !== e || X !== a
        ? ((fs = null), (ss = Je() + 500), pu(e, a))
        : (Su = Ao(e, a));
      e: do
        try {
          if (J !== 0 && G !== null) {
            a = G;
            var o = je;
            a: switch (J) {
              case 1:
                ((J = 0), (je = null), Wl(e, a, o, 1));
                break;
              case 2:
              case 9:
                if (oc(o)) {
                  ((J = 0), (je = null), Dc(a));
                  break;
                }
                ((a = function () {
                  ((J !== 2 && J !== 9) || ae !== e || (J = 7), qa(e));
                }),
                  o.then(a, a));
                break e;
              case 3:
                J = 7;
                break e;
              case 4:
                J = 5;
                break e;
              case 7:
                oc(o)
                  ? ((J = 0), (je = null), Dc(a))
                  : ((J = 0), (je = null), Wl(e, a, o, 7));
                break;
              case 5:
                var d = null;
                switch (G.tag) {
                  case 26:
                    d = G.memoizedState;
                  case 5:
                  case 27:
                    var f = G;
                    if (d ? Eh(d) : f.stateNode.complete) {
                      ((J = 0), (je = null));
                      var n = f.sibling;
                      if (n !== null) G = n;
                      else {
                        var p = f.return;
                        p !== null ? ((G = p), zs(p)) : (G = null);
                      }
                      break a;
                    }
                }
                ((J = 0), (je = null), Wl(e, a, o, 5));
                break;
              case 6:
                ((J = 0), (je = null), Wl(e, a, o, 6));
                break;
              case 8:
                (Bi(), (re = 6));
                break e;
              default:
                throw Error(v(462));
            }
          }
          kC();
          break;
        } catch (h) {
          rh(e, h);
        }
      while (!0);
      return (
        ($a = Il = null),
        (R.H = l),
        (R.A = u),
        (Q = t),
        G !== null ? 0 : ((ae = null), (X = 0), Is(), re)
      );
    }
    function kC() {
      for (; G !== null && !Qg(); ) hh(G);
    }
    function hh(e) {
      var a = _p(e.alternate, e, st);
      ((e.memoizedProps = e.pendingProps), a === null ? zs(e) : (G = a));
    }
    function Dc(e) {
      var a = e,
        t = a.alternate;
      switch (a.tag) {
        case 15:
        case 0:
          a = vc(t, a, a.pendingProps, a.type, void 0, X);
          break;
        case 11:
          a = vc(t, a, a.pendingProps, a.type.render, a.ref, X);
          break;
        case 5:
          Li(a);
        default:
          (Gp(t, a), (a = G = Gm(a, st)), (a = _p(t, a, st)));
      }
      ((e.memoizedProps = e.pendingProps), a === null ? zs(e) : (G = a));
    }
    function Wl(e, a, t, l) {
      (($a = Il = null), Li(a), (lu = null), (ho = 0));
      var u = a.return;
      try {
        if (pC(e, u, a, t, X)) {
          ((re = 1), us(e, pa(t, e.current)), (G = null));
          return;
        }
      } catch (o) {
        if (u !== null) throw ((G = u), o);
        ((re = 1), us(e, pa(t, e.current)), (G = null));
        return;
      }
      a.flags & 32768
        ? (Y || l === 1
            ? (e = !0)
            : Su || (X & 536870912) !== 0
              ? (e = !1)
              : ((It = e = !0),
                (l === 2 || l === 9 || l === 3 || l === 6) &&
                  ((l = ta.current),
                  l !== null && l.tag === 13 && (l.flags |= 16384))),
          Lh(a, e))
        : zs(a);
    }
    function zs(e) {
      var a = e;
      do {
        if ((a.flags & 32768) !== 0) {
          Lh(a, It);
          return;
        }
        e = a.return;
        var t = gC(a.alternate, a, st);
        if (t !== null) {
          G = t;
          return;
        }
        if (((a = a.sibling), a !== null)) {
          G = a;
          return;
        }
        G = a = e;
      } while (a !== null);
      re === 0 && (re = 5);
    }
    function Lh(e, a) {
      do {
        var t = xC(e.alternate, e);
        if (t !== null) {
          ((t.flags &= 32767), (G = t));
          return;
        }
        if (
          ((t = e.return),
          t !== null &&
            ((t.flags |= 32768), (t.subtreeFlags = 0), (t.deletions = null)),
          !a && ((e = e.sibling), e !== null))
        ) {
          G = e;
          return;
        }
        G = e = t;
      } while (e !== null);
      ((re = 6), (G = null));
    }
    function Tc(e, a, t, l, u, o, d, f, n) {
      e.cancelPendingCommit = null;
      do Rs();
      while (Ce !== 0);
      if ((Q & 6) !== 0) throw Error(v(327));
      if (a !== null) {
        if (a === e.current) throw Error(v(177));
        if (
          ((o = a.lanes | a.childLanes),
          (o |= ti),
          ox(e, t, o, d, f, n),
          e === ae && ((G = ae = null), (X = 0)),
          (mu = a),
          (Bt = e),
          (tt = t),
          (Tn = o),
          (Bn = u),
          (fh = l),
          (a.subtreeFlags & 10256) !== 0 || (a.flags & 10256) !== 0
            ? ((e.callbackNode = null),
              (e.callbackPriority = 0),
              TC(jd, function () {
                return (Sh(), null);
              }))
            : ((e.callbackNode = null), (e.callbackPriority = 0)),
          (l = (a.flags & 13878) !== 0),
          (a.subtreeFlags & 13878) !== 0 || l)
        ) {
          ((l = R.T), (R.T = null), (u = K.p), (K.p = 2), (d = Q), (Q |= 4));
          try {
            CC(e, a, t);
          } finally {
            ((Q = d), (K.p = u), (R.T = l));
          }
        }
        ((Ce = 1), gh(), xh(), Ch());
      }
    }
    function gh() {
      if (Ce === 1) {
        Ce = 0;
        var e = Bt,
          a = mu,
          t = (a.flags & 13878) !== 0;
        if ((a.subtreeFlags & 13878) !== 0 || t) {
          ((t = R.T), (R.T = null));
          var l = K.p;
          K.p = 2;
          var u = Q;
          Q |= 4;
          try {
            eh(a, e);
            var o = qn,
              d = Em(e.containerInfo),
              f = o.focusedElem,
              n = o.selectionRange;
            if (
              d !== f &&
              f &&
              f.ownerDocument &&
              Om(f.ownerDocument.documentElement, f)
            ) {
              if (n !== null && ai(f)) {
                var p = n.start,
                  h = n.end;
                if ((h === void 0 && (h = p), "selectionStart" in f))
                  ((f.selectionStart = p),
                    (f.selectionEnd = Math.min(h, f.value.length)));
                else {
                  var x = f.ownerDocument || document,
                    m = (x && x.defaultView) || window;
                  if (m.getSelection) {
                    var L = m.getSelection(),
                      k = f.textContent.length,
                      w = Math.min(n.start, k),
                      A = n.end === void 0 ? w : Math.min(n.end, k);
                    !L.extend && w > A && ((d = A), (A = w), (w = d));
                    var c = Wr(f, w),
                      r = Wr(f, A);
                    if (
                      c &&
                      r &&
                      (L.rangeCount !== 1 ||
                        L.anchorNode !== c.node ||
                        L.anchorOffset !== c.offset ||
                        L.focusNode !== r.node ||
                        L.focusOffset !== r.offset)
                    ) {
                      var g = x.createRange();
                      (g.setStart(c.node, c.offset),
                        L.removeAllRanges(),
                        w > A
                          ? (L.addRange(g), L.extend(r.node, r.offset))
                          : (g.setEnd(r.node, r.offset), L.addRange(g)));
                    }
                  }
                }
              }
              for (x = [], L = f; (L = L.parentNode); )
                L.nodeType === 1 &&
                  x.push({ element: L, left: L.scrollLeft, top: L.scrollTop });
              for (
                typeof f.focus == "function" && f.focus(), f = 0;
                f < x.length;
                f++
              ) {
                var y = x[f];
                ((y.element.scrollLeft = y.left),
                  (y.element.scrollTop = y.top));
              }
            }
            ((gs = !!En), (qn = En = null));
          } finally {
            ((Q = u), (K.p = l), (R.T = t));
          }
        }
        ((e.current = a), (Ce = 2));
      }
    }
    function xh() {
      if (Ce === 2) {
        Ce = 0;
        var e = Bt,
          a = mu,
          t = (a.flags & 8772) !== 0;
        if ((a.subtreeFlags & 8772) !== 0 || t) {
          ((t = R.T), (R.T = null));
          var l = K.p;
          K.p = 2;
          var u = Q;
          Q |= 4;
          try {
            Qp(e, a.alternate, a);
          } finally {
            ((Q = u), (K.p = l), (R.T = t));
          }
        }
        Ce = 3;
      }
    }
    function Ch() {
      if (Ce === 4 || Ce === 3) {
        ((Ce = 0), Kg());
        var e = Bt,
          a = mu,
          t = tt,
          l = fh;
        (a.subtreeFlags & 10256) !== 0 || (a.flags & 10256) !== 0
          ? (Ce = 5)
          : ((Ce = 0), (mu = Bt = null), yh(e, e.pendingLanes));
        var u = e.pendingLanes;
        if (
          (u === 0 && (Tt = null),
          Zn(t),
          (a = a.stateNode),
          We && typeof We.onCommitFiberRoot == "function")
        )
          try {
            We.onCommitFiberRoot(
              bo,
              a,
              void 0,
              (a.current.flags & 128) === 128,
            );
          } catch {}
        if (l !== null) {
          ((a = R.T), (u = K.p), (K.p = 2), (R.T = null));
          try {
            for (var o = e.onRecoverableError, d = 0; d < l.length; d++) {
              var f = l[d];
              o(f.value, { componentStack: f.stack });
            }
          } finally {
            ((R.T = a), (K.p = u));
          }
        }
        ((tt & 3) !== 0 && Rs(),
          qa(e),
          (u = e.pendingLanes),
          (t & 261930) !== 0 && (u & 42) !== 0
            ? e === zn
              ? so++
              : ((so = 0), (zn = e))
            : (so = 0),
          zo(0, !1));
      }
    }
    function yh(e, a) {
      (e.pooledCacheLanes &= a) === 0 &&
        ((a = e.pooledCache), a != null && ((e.pooledCache = null), Do(a)));
    }
    function Rs() {
      return (gh(), xh(), Ch(), Sh());
    }
    function Sh() {
      if (Ce !== 5) return !1;
      var e = Bt,
        a = Tn;
      Tn = 0;
      var t = Zn(tt),
        l = R.T,
        u = K.p;
      try {
        ((K.p = 32 > t ? 32 : t), (R.T = null), (t = Bn), (Bn = null));
        var o = Bt,
          d = tt;
        if (((Ce = 0), (mu = Bt = null), (tt = 0), (Q & 6) !== 0))
          throw Error(v(331));
        var f = Q;
        if (
          ((Q |= 4),
          oh(o.current),
          th(o, o.current, d, t),
          (Q = f),
          zo(0, !1),
          We && typeof We.onPostCommitFiberRoot == "function")
        )
          try {
            We.onPostCommitFiberRoot(bo, o);
          } catch {}
        return !0;
      } finally {
        ((K.p = u), (R.T = l), yh(e, a));
      }
    }
    function Bc(e, a, t) {
      ((a = pa(t, a)),
        (a = An(e.stateNode, a, 2)),
        (e = Dt(e, a, 2)),
        e !== null && (ko(e, 2), qa(e)));
    }
    function W(e, a, t) {
      if (e.tag === 3) Bc(e, e, t);
      else
        for (; a !== null; ) {
          if (a.tag === 3) {
            Bc(a, e, t);
            break;
          } else if (a.tag === 1) {
            var l = a.stateNode;
            if (
              typeof a.type.getDerivedStateFromError == "function" ||
              (typeof l.componentDidCatch == "function" &&
                (Tt === null || !Tt.has(l)))
            ) {
              ((e = pa(t, e)),
                (t = qp(2)),
                (l = Dt(a, t, 2)),
                l !== null && (Np(t, l, a, e), ko(l, 2), qa(l)));
              break;
            }
          }
          a = a.return;
        }
    }
    function _f(e, a, t) {
      var l = e.pingCache;
      if (l === null) {
        l = e.pingCache = new vC();
        var u = new Set();
        l.set(a, u);
      } else ((u = l.get(a)), u === void 0 && ((u = new Set()), l.set(a, u)));
      u.has(t) ||
        ((Di = !0), u.add(t), (e = MC.bind(null, e, a, t)), a.then(e, e));
    }
    function MC(e, a, t) {
      var l = e.pingCache;
      (l !== null && l.delete(a),
        (e.pingedLanes |= e.suspendedLanes & t),
        (e.warmLanes &= ~t),
        ae === e &&
          (X & t) === t &&
          (re === 4 || (re === 3 && (X & 62914560) === X && 300 > Je() - Ts)
            ? (Q & 2) === 0 && pu(e, 0)
            : (Ti |= t),
          cu === X && (cu = 0)),
        qa(e));
    }
    function vh(e, a) {
      (a === 0 && (a = mm()), (e = vl(e, a)), e !== null && (ko(e, a), qa(e)));
    }
    function wC(e) {
      var a = e.memoizedState,
        t = 0;
      (a !== null && (t = a.retryLane), vh(e, t));
    }
    function DC(e, a) {
      var t = 0;
      switch (e.tag) {
        case 31:
        case 13:
          var l = e.stateNode,
            u = e.memoizedState;
          u !== null && (t = u.retryLane);
          break;
        case 19:
          l = e.stateNode;
          break;
        case 22:
          l = e.stateNode._retryCache;
          break;
        default:
          throw Error(v(314));
      }
      (l !== null && l.delete(a), vh(e, t));
    }
    function TC(e, a) {
      return Yn(e, a);
    }
    var is = null,
      Fl = null,
      Rn = !1,
      rs = !1,
      Gf = !1,
      At = 0;
    function qa(e) {
      (e !== Fl &&
        e.next === null &&
        (Fl === null ? (is = Fl = e) : (Fl = Fl.next = e)),
        (rs = !0),
        Rn || ((Rn = !0), zC()));
    }
    function zo(e, a) {
      if (!Gf && rs) {
        Gf = !0;
        do
          for (var t = !1, l = is; l !== null; ) {
            if (!a)
              if (e !== 0) {
                var u = l.pendingLanes;
                if (u === 0) var o = 0;
                else {
                  var d = l.suspendedLanes,
                    f = l.pingedLanes;
                  ((o = (1 << (31 - $e(42 | e) + 1)) - 1),
                    (o &= u & ~(d & ~f)),
                    (o = o & 201326741 ? (o & 201326741) | 1 : o ? o | 2 : 0));
                }
                o !== 0 && ((t = !0), zc(l, o));
              } else
                ((o = X),
                  (o = Cs(
                    l,
                    l === ae ? o : 0,
                    l.cancelPendingCommit !== null || l.timeoutHandle !== -1,
                  )),
                  (o & 3) === 0 || Ao(l, o) || ((t = !0), zc(l, o)));
            l = l.next;
          }
        while (t);
        Gf = !1;
      }
    }
    function BC() {
      Ih();
    }
    function Ih() {
      rs = Rn = !1;
      var e = 0;
      At !== 0 && FC() && (e = At);
      for (var a = Je(), t = null, l = is; l !== null; ) {
        var u = l.next,
          o = bh(l, a);
        (o === 0
          ? ((l.next = null),
            t === null ? (is = u) : (t.next = u),
            u === null && (Fl = t))
          : ((t = l), (e !== 0 || (o & 3) !== 0) && (rs = !0)),
          (l = u));
      }
      ((Ce !== 0 && Ce !== 5) || zo(e, !1), At !== 0 && (At = 0));
    }
    function bh(e, a) {
      for (
        var t = e.suspendedLanes,
          l = e.pingedLanes,
          u = e.expirationTimes,
          o = e.pendingLanes & -62914561;
        0 < o;
      ) {
        var d = 31 - $e(o),
          f = 1 << d,
          n = u[d];
        (n === -1
          ? ((f & t) === 0 || (f & l) !== 0) && (u[d] = ux(f, a))
          : n <= a && (e.expiredLanes |= f),
          (o &= ~f));
      }
      if (
        ((a = ae),
        (t = X),
        (t = Cs(
          e,
          e === a ? t : 0,
          e.cancelPendingCommit !== null || e.timeoutHandle !== -1,
        )),
        (l = e.callbackNode),
        t === 0 ||
          (e === a && (J === 2 || J === 9)) ||
          e.cancelPendingCommit !== null)
      )
        return (
          l !== null && l !== null && xf(l),
          (e.callbackNode = null),
          (e.callbackPriority = 0)
        );
      if ((t & 3) === 0 || Ao(e, t)) {
        if (((a = t & -t), a === e.callbackPriority)) return a;
        switch ((l !== null && xf(l), Zn(t))) {
          case 2:
          case 8:
            t = rm;
            break;
          case 32:
            t = jd;
            break;
          case 268435456:
            t = cm;
            break;
          default:
            t = jd;
        }
        return (
          (l = Ah.bind(null, e)),
          (t = Yn(t, l)),
          (e.callbackPriority = a),
          (e.callbackNode = t),
          a
        );
      }
      return (
        l !== null && l !== null && xf(l),
        (e.callbackPriority = 2),
        (e.callbackNode = null),
        2
      );
    }
    function Ah(e, a) {
      if (Ce !== 0 && Ce !== 5)
        return ((e.callbackNode = null), (e.callbackPriority = 0), null);
      var t = e.callbackNode;
      if (Rs() && e.callbackNode !== t) return null;
      var l = X;
      return (
        (l = Cs(
          e,
          e === ae ? l : 0,
          e.cancelPendingCommit !== null || e.timeoutHandle !== -1,
        )),
        l === 0
          ? null
          : (ih(e, l, a),
            bh(e, Je()),
            e.callbackNode != null && e.callbackNode === t
              ? Ah.bind(null, e)
              : null)
      );
    }
    function zc(e, a) {
      if (Rs()) return null;
      ih(e, a, !0);
    }
    function zC() {
      _C(function () {
        (Q & 6) !== 0 ? Yn(im, BC) : Ih();
      });
    }
    function zi() {
      if (At === 0) {
        var e = nu;
        (e === 0 && ((e = cd), (cd <<= 1), (cd & 261888) === 0 && (cd = 256)),
          (At = e));
      }
      return At;
    }
    function Rc(e) {
      return e == null || typeof e == "symbol" || typeof e == "boolean"
        ? null
        : typeof e == "function"
          ? e
          : Td("" + e);
    }
    function Oc(e, a) {
      var t = a.ownerDocument.createElement("input");
      return (
        (t.name = a.name),
        (t.value = a.value),
        e.id && t.setAttribute("form", e.id),
        a.parentNode.insertBefore(t, a),
        (e = new FormData(e)),
        t.parentNode.removeChild(t),
        e
      );
    }
    function RC(e, a, t, l, u) {
      if (a === "submit" && t && t.stateNode === u) {
        var o = Rc((u[Ge] || null).action),
          d = l.submitter;
        d &&
          ((a = (a = d[Ge] || null)
            ? Rc(a.formAction)
            : d.getAttribute("formAction")),
          a !== null && ((o = a), (d = null)));
        var f = new ys("action", "action", null, l, u);
        e.push({
          event: f,
          listeners: [
            {
              instance: null,
              listener: function () {
                if (l.defaultPrevented) {
                  if (At !== 0) {
                    var n = d ? Oc(u, d) : new FormData(u);
                    In(
                      t,
                      { pending: !0, data: n, method: u.method, action: o },
                      null,
                      n,
                    );
                  }
                } else
                  typeof o == "function" &&
                    (f.preventDefault(),
                    (n = d ? Oc(u, d) : new FormData(u)),
                    In(
                      t,
                      { pending: !0, data: n, method: u.method, action: o },
                      o,
                      n,
                    ));
              },
              currentTarget: u,
            },
          ],
        });
      }
    }
    for (bd = 0; bd < rn.length; bd++)
      ((Ad = rn[bd]),
        (Ec = Ad.toLowerCase()),
        (qc = Ad[0].toUpperCase() + Ad.slice(1)),
        Ia(Ec, "on" + qc));
    var Ad, Ec, qc, bd;
    Ia(Nm, "onAnimationEnd");
    Ia(Um, "onAnimationIteration");
    Ia(Hm, "onAnimationStart");
    Ia("dblclick", "onDoubleClick");
    Ia("focusin", "onFocus");
    Ia("focusout", "onBlur");
    Ia(Jx, "onTransitionRun");
    Ia(Wx, "onTransitionStart");
    Ia($x, "onTransitionCancel");
    Ia(Fm, "onTransitionEnd");
    su("onMouseEnter", ["mouseout", "mouseover"]);
    su("onMouseLeave", ["mouseout", "mouseover"]);
    su("onPointerEnter", ["pointerout", "pointerover"]);
    su("onPointerLeave", ["pointerout", "pointerover"]);
    Cl(
      "onChange",
      "change click focusin focusout input keydown keyup selectionchange".split(
        " ",
      ),
    );
    Cl(
      "onSelect",
      "focusout contextmenu dragend focusin keydown keyup mousedown mouseup selectionchange".split(
        " ",
      ),
    );
    Cl("onBeforeInput", ["compositionend", "keypress", "textInput", "paste"]);
    Cl(
      "onCompositionEnd",
      "compositionend focusout keydown keypress keyup mousedown".split(" "),
    );
    Cl(
      "onCompositionStart",
      "compositionstart focusout keydown keypress keyup mousedown".split(" "),
    );
    Cl(
      "onCompositionUpdate",
      "compositionupdate focusout keydown keypress keyup mousedown".split(" "),
    );
    var xo =
        "abort canplay canplaythrough durationchange emptied encrypted ended error loadeddata loadedmetadata loadstart pause play playing progress ratechange resize seeked seeking stalled suspend timeupdate volumechange waiting".split(
          " ",
        ),
      OC = new Set(
        "beforetoggle cancel close invalid load scroll scrollend toggle"
          .split(" ")
          .concat(xo),
      );
    function kh(e, a) {
      a = (a & 4) !== 0;
      for (var t = 0; t < e.length; t++) {
        var l = e[t],
          u = l.event;
        l = l.listeners;
        e: {
          var o = void 0;
          if (a)
            for (var d = l.length - 1; 0 <= d; d--) {
              var f = l[d],
                n = f.instance,
                p = f.currentTarget;
              if (((f = f.listener), n !== o && u.isPropagationStopped()))
                break e;
              ((o = f), (u.currentTarget = p));
              try {
                o(u);
              } catch (h) {
                Qd(h);
              }
              ((u.currentTarget = null), (o = n));
            }
          else
            for (d = 0; d < l.length; d++) {
              if (
                ((f = l[d]),
                (n = f.instance),
                (p = f.currentTarget),
                (f = f.listener),
                n !== o && u.isPropagationStopped())
              )
                break e;
              ((o = f), (u.currentTarget = p));
              try {
                o(u);
              } catch (h) {
                Qd(h);
              }
              ((u.currentTarget = null), (o = n));
            }
        }
      }
    }
    function _(e, a) {
      var t = a[tn];
      t === void 0 && (t = a[tn] = new Set());
      var l = e + "__bubble";
      t.has(l) || (Mh(a, e, 2, !1), t.add(l));
    }
    function Vf(e, a, t) {
      var l = 0;
      (a && (l |= 4), Mh(t, e, l, a));
    }
    var kd = "_reactListening" + Math.random().toString(36).slice(2);
    function Ri(e) {
      if (!e[kd]) {
        ((e[kd] = !0),
          xm.forEach(function (t) {
            t !== "selectionchange" &&
              (OC.has(t) || Vf(t, !1, e), Vf(t, !0, e));
          }));
        var a = e.nodeType === 9 ? e : e.ownerDocument;
        a === null || a[kd] || ((a[kd] = !0), Vf("selectionchange", !1, a));
      }
    }
    function Mh(e, a, t, l) {
      switch (Fh(a)) {
        case 2:
          var u = fy;
          break;
        case 8:
          u = ny;
          break;
        default:
          u = Ni;
      }
      ((t = u.bind(null, a, t, e)),
        (u = void 0),
        !sn ||
          (a !== "touchstart" && a !== "touchmove" && a !== "wheel") ||
          (u = !0),
        l
          ? u !== void 0
            ? e.addEventListener(a, t, { capture: !0, passive: u })
            : e.addEventListener(a, t, !0)
          : u !== void 0
            ? e.addEventListener(a, t, { passive: u })
            : e.addEventListener(a, t, !1));
    }
    function Xf(e, a, t, l, u) {
      var o = l;
      if ((a & 1) === 0 && (a & 2) === 0 && l !== null)
        e: for (;;) {
          if (l === null) return;
          var d = l.tag;
          if (d === 3 || d === 4) {
            var f = l.stateNode.containerInfo;
            if (f === u) break;
            if (d === 4)
              for (d = l.return; d !== null; ) {
                var n = d.tag;
                if ((n === 3 || n === 4) && d.stateNode.containerInfo === u)
                  return;
                d = d.return;
              }
            for (; f !== null; ) {
              if (((d = Gl(f)), d === null)) return;
              if (((n = d.tag), n === 5 || n === 6 || n === 26 || n === 27)) {
                l = o = d;
                continue e;
              }
              f = f.parentNode;
            }
          }
          l = l.return;
        }
      km(function () {
        var p = o,
          h = Jn(t),
          x = [];
        e: {
          var m = Pm.get(e);
          if (m !== void 0) {
            var L = ys,
              k = e;
            switch (e) {
              case "keypress":
                if (zd(t) === 0) break e;
              case "keydown":
              case "keyup":
                L = Dx;
                break;
              case "focusin":
                ((k = "focus"), (L = If));
                break;
              case "focusout":
                ((k = "blur"), (L = If));
                break;
              case "beforeblur":
              case "afterblur":
                L = If;
                break;
              case "click":
                if (t.button === 2) break e;
              case "auxclick":
              case "dblclick":
              case "mousedown":
              case "mousemove":
              case "mouseup":
              case "mouseout":
              case "mouseover":
              case "contextmenu":
                L = Gr;
                break;
              case "drag":
              case "dragend":
              case "dragenter":
              case "dragexit":
              case "dragleave":
              case "dragover":
              case "dragstart":
              case "drop":
                L = gx;
                break;
              case "touchcancel":
              case "touchend":
              case "touchmove":
              case "touchstart":
                L = zx;
                break;
              case Nm:
              case Um:
              case Hm:
                L = yx;
                break;
              case Fm:
                L = Ox;
                break;
              case "scroll":
              case "scrollend":
                L = hx;
                break;
              case "wheel":
                L = qx;
                break;
              case "copy":
              case "cut":
              case "paste":
                L = vx;
                break;
              case "gotpointercapture":
              case "lostpointercapture":
              case "pointercancel":
              case "pointerdown":
              case "pointermove":
              case "pointerout":
              case "pointerover":
              case "pointerup":
                L = Xr;
                break;
              case "toggle":
              case "beforetoggle":
                L = Ux;
            }
            var w = (a & 4) !== 0,
              A = !w && (e === "scroll" || e === "scrollend"),
              c = w ? (m !== null ? m + "Capture" : null) : m;
            w = [];
            for (var r = p, g; r !== null; ) {
              var y = r;
              if (
                ((g = y.stateNode),
                (y = y.tag),
                (y !== 5 && y !== 26 && y !== 27) ||
                  g === null ||
                  c === null ||
                  ((y = io(r, c)), y != null && w.push(Co(r, y, g))),
                A)
              )
                break;
              r = r.return;
            }
            0 < w.length &&
              ((m = new L(m, k, null, t, h)),
              x.push({ event: m, listeners: w }));
          }
        }
        if ((a & 7) === 0) {
          e: {
            if (
              ((m = e === "mouseover" || e === "pointerover"),
              (L = e === "mouseout" || e === "pointerout"),
              m &&
                t !== dn &&
                (k = t.relatedTarget || t.fromElement) &&
                (Gl(k) || k[gu]))
            )
              break e;
            if (
              (L || m) &&
              ((m =
                h.window === h
                  ? h
                  : (m = h.ownerDocument)
                    ? m.defaultView || m.parentWindow
                    : window),
              L
                ? ((k = t.relatedTarget || t.toElement),
                  (L = p),
                  (k = k ? Gl(k) : null),
                  k !== null &&
                    ((A = Io(k)),
                    (w = k.tag),
                    k !== A || (w !== 5 && w !== 27 && w !== 6)) &&
                    (k = null))
                : ((L = null), (k = p)),
              L !== k)
            ) {
              if (
                ((w = Gr),
                (y = "onMouseLeave"),
                (c = "onMouseEnter"),
                (r = "mouse"),
                (e === "pointerout" || e === "pointerover") &&
                  ((w = Xr),
                  (y = "onPointerLeave"),
                  (c = "onPointerEnter"),
                  (r = "pointer")),
                (A = L == null ? m : Yu(L)),
                (g = k == null ? m : Yu(k)),
                (m = new w(y, r + "leave", L, t, h)),
                (m.target = A),
                (m.relatedTarget = g),
                (y = null),
                Gl(h) === p &&
                  ((w = new w(c, r + "enter", k, t, h)),
                  (w.target = g),
                  (w.relatedTarget = A),
                  (y = w)),
                (A = y),
                L && k)
              )
                a: {
                  for (w = EC, c = L, r = k, g = 0, y = c; y; y = w(y)) g++;
                  y = 0;
                  for (var T = r; T; T = w(T)) y++;
                  for (; 0 < g - y; ) ((c = w(c)), g--);
                  for (; 0 < y - g; ) ((r = w(r)), y--);
                  for (; g--; ) {
                    if (c === r || (r !== null && c === r.alternate)) {
                      w = c;
                      break a;
                    }
                    ((c = w(c)), (r = w(r)));
                  }
                  w = null;
                }
              else w = null;
              (L !== null && Nc(x, m, L, w, !1),
                k !== null && A !== null && Nc(x, A, k, w, !0));
            }
          }
          e: {
            if (
              ((m = p ? Yu(p) : window),
              (L = m.nodeName && m.nodeName.toLowerCase()),
              L === "select" || (L === "input" && m.type === "file"))
            )
              var V = Qr;
            else if (Zr(m))
              if (zm) V = Zx;
              else {
                V = Yx;
                var D = Xx;
              }
            else
              ((L = m.nodeName),
                !L ||
                L.toLowerCase() !== "input" ||
                (m.type !== "checkbox" && m.type !== "radio")
                  ? p && Kn(p.elementType) && (V = Qr)
                  : (V = jx));
            if (V && (V = V(e, p))) {
              Bm(x, V, t, h);
              break e;
            }
            (D && D(e, m, p),
              e === "focusout" &&
                p &&
                m.type === "number" &&
                p.memoizedProps.value != null &&
                on(m, "number", m.value));
          }
          switch (((D = p ? Yu(p) : window), e)) {
            case "focusin":
              (Zr(D) || D.contentEditable === "true") &&
                ((Yl = D), (fn = p), (Ju = null));
              break;
            case "focusout":
              Ju = fn = Yl = null;
              break;
            case "mousedown":
              nn = !0;
              break;
            case "contextmenu":
            case "mouseup":
            case "dragend":
              ((nn = !1), $r(x, t, h));
              break;
            case "selectionchange":
              if (Kx) break;
            case "keydown":
            case "keyup":
              $r(x, t, h);
          }
          var B;
          if (ei)
            e: {
              switch (e) {
                case "compositionstart":
                  var U = "onCompositionStart";
                  break e;
                case "compositionend":
                  U = "onCompositionEnd";
                  break e;
                case "compositionupdate":
                  U = "onCompositionUpdate";
                  break e;
              }
              U = void 0;
            }
          else
            Xl
              ? Dm(e, t) && (U = "onCompositionEnd")
              : e === "keydown" &&
                t.keyCode === 229 &&
                (U = "onCompositionStart");
          (U &&
            (wm &&
              t.locale !== "ko" &&
              (Xl || U !== "onCompositionStart"
                ? U === "onCompositionEnd" && Xl && (B = Mm())
                : ((vt = h),
                  (Wn = "value" in vt ? vt.value : vt.textContent),
                  (Xl = !0))),
            (D = cs(p, U)),
            0 < D.length &&
              ((U = new Vr(U, e, null, t, h)),
              x.push({ event: U, listeners: D }),
              B ? (U.data = B) : ((B = Tm(t)), B !== null && (U.data = B)))),
            (B = Fx ? Px(e, t) : _x(e, t)) &&
              ((U = cs(p, "onBeforeInput")),
              0 < U.length &&
                ((D = new Vr("onBeforeInput", "beforeinput", null, t, h)),
                x.push({ event: D, listeners: U }),
                (D.data = B))),
            RC(x, e, p, t, h));
        }
        kh(x, a);
      });
    }
    function Co(e, a, t) {
      return { instance: e, listener: a, currentTarget: t };
    }
    function cs(e, a) {
      for (var t = a + "Capture", l = []; e !== null; ) {
        var u = e,
          o = u.stateNode;
        if (
          ((u = u.tag),
          (u !== 5 && u !== 26 && u !== 27) ||
            o === null ||
            ((u = io(e, t)),
            u != null && l.unshift(Co(e, u, o)),
            (u = io(e, a)),
            u != null && l.push(Co(e, u, o))),
          e.tag === 3)
        )
          return l;
        e = e.return;
      }
      return [];
    }
    function EC(e) {
      if (e === null) return null;
      do e = e.return;
      while (e && e.tag !== 5 && e.tag !== 27);
      return e || null;
    }
    function Nc(e, a, t, l, u) {
      for (var o = a._reactName, d = []; t !== null && t !== l; ) {
        var f = t,
          n = f.alternate,
          p = f.stateNode;
        if (((f = f.tag), n !== null && n === l)) break;
        ((f !== 5 && f !== 26 && f !== 27) ||
          p === null ||
          ((n = p),
          u
            ? ((p = io(t, o)), p != null && d.unshift(Co(t, p, n)))
            : u || ((p = io(t, o)), p != null && d.push(Co(t, p, n)))),
          (t = t.return));
      }
      d.length !== 0 && e.push({ event: a, listeners: d });
    }
    var qC = /\r\n?/g,
      NC = /\u0000|\uFFFD/g;
    function Uc(e) {
      return (typeof e == "string" ? e : "" + e)
        .replace(
          qC,
          `
`,
        )
        .replace(NC, "");
    }
    function wh(e, a) {
      return ((a = Uc(a)), Uc(e) === a);
    }
    function $(e, a, t, l, u, o) {
      switch (t) {
        case "children":
          typeof l == "string"
            ? a === "body" || (a === "textarea" && l === "") || fu(e, l)
            : (typeof l == "number" || typeof l == "bigint") &&
              a !== "body" &&
              fu(e, "" + l);
          break;
        case "className":
          hd(e, "class", l);
          break;
        case "tabIndex":
          hd(e, "tabindex", l);
          break;
        case "dir":
        case "role":
        case "viewBox":
        case "width":
        case "height":
          hd(e, t, l);
          break;
        case "style":
          Am(e, l, o);
          break;
        case "data":
          if (a !== "object") {
            hd(e, "data", l);
            break;
          }
        case "src":
        case "href":
          if (l === "" && (a !== "a" || t !== "href")) {
            e.removeAttribute(t);
            break;
          }
          if (
            l == null ||
            typeof l == "function" ||
            typeof l == "symbol" ||
            typeof l == "boolean"
          ) {
            e.removeAttribute(t);
            break;
          }
          ((l = Td("" + l)), e.setAttribute(t, l));
          break;
        case "action":
        case "formAction":
          if (typeof l == "function") {
            e.setAttribute(
              t,
              "javascript:throw new Error('A React form was unexpectedly submitted. If you called form.submit() manually, consider using form.requestSubmit() instead. If you\\'re trying to use event.stopPropagation() in a submit event handler, consider also calling event.preventDefault().')",
            );
            break;
          } else
            typeof o == "function" &&
              (t === "formAction"
                ? (a !== "input" && $(e, a, "name", u.name, u, null),
                  $(e, a, "formEncType", u.formEncType, u, null),
                  $(e, a, "formMethod", u.formMethod, u, null),
                  $(e, a, "formTarget", u.formTarget, u, null))
                : ($(e, a, "encType", u.encType, u, null),
                  $(e, a, "method", u.method, u, null),
                  $(e, a, "target", u.target, u, null)));
          if (l == null || typeof l == "symbol" || typeof l == "boolean") {
            e.removeAttribute(t);
            break;
          }
          ((l = Td("" + l)), e.setAttribute(t, l));
          break;
        case "onClick":
          l != null && (e.onclick = Wa);
          break;
        case "onScroll":
          l != null && _("scroll", e);
          break;
        case "onScrollEnd":
          l != null && _("scrollend", e);
          break;
        case "dangerouslySetInnerHTML":
          if (l != null) {
            if (typeof l != "object" || !("__html" in l)) throw Error(v(61));
            if (((t = l.__html), t != null)) {
              if (u.children != null) throw Error(v(60));
              e.innerHTML = t;
            }
          }
          break;
        case "multiple":
          e.multiple = l && typeof l != "function" && typeof l != "symbol";
          break;
        case "muted":
          e.muted = l && typeof l != "function" && typeof l != "symbol";
          break;
        case "suppressContentEditableWarning":
        case "suppressHydrationWarning":
        case "defaultValue":
        case "defaultChecked":
        case "innerHTML":
        case "ref":
          break;
        case "autoFocus":
          break;
        case "xlinkHref":
          if (
            l == null ||
            typeof l == "function" ||
            typeof l == "boolean" ||
            typeof l == "symbol"
          ) {
            e.removeAttribute("xlink:href");
            break;
          }
          ((t = Td("" + l)),
            e.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", t));
          break;
        case "contentEditable":
        case "spellCheck":
        case "draggable":
        case "value":
        case "autoReverse":
        case "externalResourcesRequired":
        case "focusable":
        case "preserveAlpha":
          l != null && typeof l != "function" && typeof l != "symbol"
            ? e.setAttribute(t, "" + l)
            : e.removeAttribute(t);
          break;
        case "inert":
        case "allowFullScreen":
        case "async":
        case "autoPlay":
        case "controls":
        case "default":
        case "defer":
        case "disabled":
        case "disablePictureInPicture":
        case "disableRemotePlayback":
        case "formNoValidate":
        case "hidden":
        case "loop":
        case "noModule":
        case "noValidate":
        case "open":
        case "playsInline":
        case "readOnly":
        case "required":
        case "reversed":
        case "scoped":
        case "seamless":
        case "itemScope":
          l && typeof l != "function" && typeof l != "symbol"
            ? e.setAttribute(t, "")
            : e.removeAttribute(t);
          break;
        case "capture":
        case "download":
          l === !0
            ? e.setAttribute(t, "")
            : l !== !1 &&
                l != null &&
                typeof l != "function" &&
                typeof l != "symbol"
              ? e.setAttribute(t, l)
              : e.removeAttribute(t);
          break;
        case "cols":
        case "rows":
        case "size":
        case "span":
          l != null &&
          typeof l != "function" &&
          typeof l != "symbol" &&
          !isNaN(l) &&
          1 <= l
            ? e.setAttribute(t, l)
            : e.removeAttribute(t);
          break;
        case "rowSpan":
        case "start":
          l == null ||
          typeof l == "function" ||
          typeof l == "symbol" ||
          isNaN(l)
            ? e.removeAttribute(t)
            : e.setAttribute(t, l);
          break;
        case "popover":
          (_("beforetoggle", e), _("toggle", e), Dd(e, "popover", l));
          break;
        case "xlinkActuate":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:actuate", l);
          break;
        case "xlinkArcrole":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:arcrole", l);
          break;
        case "xlinkRole":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:role", l);
          break;
        case "xlinkShow":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:show", l);
          break;
        case "xlinkTitle":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:title", l);
          break;
        case "xlinkType":
          Va(e, "http://www.w3.org/1999/xlink", "xlink:type", l);
          break;
        case "xmlBase":
          Va(e, "http://www.w3.org/XML/1998/namespace", "xml:base", l);
          break;
        case "xmlLang":
          Va(e, "http://www.w3.org/XML/1998/namespace", "xml:lang", l);
          break;
        case "xmlSpace":
          Va(e, "http://www.w3.org/XML/1998/namespace", "xml:space", l);
          break;
        case "is":
          Dd(e, "is", l);
          break;
        case "innerText":
        case "textContent":
          break;
        default:
          (!(2 < t.length) ||
            (t[0] !== "o" && t[0] !== "O") ||
            (t[1] !== "n" && t[1] !== "N")) &&
            ((t = mx.get(t) || t), Dd(e, t, l));
      }
    }
    function On(e, a, t, l, u, o) {
      switch (t) {
        case "style":
          Am(e, l, o);
          break;
        case "dangerouslySetInnerHTML":
          if (l != null) {
            if (typeof l != "object" || !("__html" in l)) throw Error(v(61));
            if (((t = l.__html), t != null)) {
              if (u.children != null) throw Error(v(60));
              e.innerHTML = t;
            }
          }
          break;
        case "children":
          typeof l == "string"
            ? fu(e, l)
            : (typeof l == "number" || typeof l == "bigint") && fu(e, "" + l);
          break;
        case "onScroll":
          l != null && _("scroll", e);
          break;
        case "onScrollEnd":
          l != null && _("scrollend", e);
          break;
        case "onClick":
          l != null && (e.onclick = Wa);
          break;
        case "suppressContentEditableWarning":
        case "suppressHydrationWarning":
        case "innerHTML":
        case "ref":
          break;
        case "innerText":
        case "textContent":
          break;
        default:
          if (!Cm.hasOwnProperty(t))
            e: {
              if (
                t[0] === "o" &&
                t[1] === "n" &&
                ((u = t.endsWith("Capture")),
                (a = t.slice(2, u ? t.length - 7 : void 0)),
                (o = e[Ge] || null),
                (o = o != null ? o[t] : null),
                typeof o == "function" && e.removeEventListener(a, o, u),
                typeof l == "function")
              ) {
                (typeof o != "function" &&
                  o !== null &&
                  (t in e
                    ? (e[t] = null)
                    : e.hasAttribute(t) && e.removeAttribute(t)),
                  e.addEventListener(a, l, u));
                break e;
              }
              t in e
                ? (e[t] = l)
                : l === !0
                  ? e.setAttribute(t, "")
                  : Dd(e, t, l);
            }
      }
    }
    function we(e, a, t) {
      switch (a) {
        case "div":
        case "span":
        case "svg":
        case "path":
        case "a":
        case "g":
        case "p":
        case "li":
          break;
        case "img":
          (_("error", e), _("load", e));
          var l = !1,
            u = !1,
            o;
          for (o in t)
            if (t.hasOwnProperty(o)) {
              var d = t[o];
              if (d != null)
                switch (o) {
                  case "src":
                    l = !0;
                    break;
                  case "srcSet":
                    u = !0;
                    break;
                  case "children":
                  case "dangerouslySetInnerHTML":
                    throw Error(v(137, a));
                  default:
                    $(e, a, o, d, t, null);
                }
            }
          (u && $(e, a, "srcSet", t.srcSet, t, null),
            l && $(e, a, "src", t.src, t, null));
          return;
        case "input":
          _("invalid", e);
          var f = (o = d = u = null),
            n = null,
            p = null;
          for (l in t)
            if (t.hasOwnProperty(l)) {
              var h = t[l];
              if (h != null)
                switch (l) {
                  case "name":
                    u = h;
                    break;
                  case "type":
                    d = h;
                    break;
                  case "checked":
                    n = h;
                    break;
                  case "defaultChecked":
                    p = h;
                    break;
                  case "value":
                    o = h;
                    break;
                  case "defaultValue":
                    f = h;
                    break;
                  case "children":
                  case "dangerouslySetInnerHTML":
                    if (h != null) throw Error(v(137, a));
                    break;
                  default:
                    $(e, a, l, h, t, null);
                }
            }
          vm(e, o, f, n, p, d, u, !1);
          return;
        case "select":
          (_("invalid", e), (l = d = o = null));
          for (u in t)
            if (t.hasOwnProperty(u) && ((f = t[u]), f != null))
              switch (u) {
                case "value":
                  o = f;
                  break;
                case "defaultValue":
                  d = f;
                  break;
                case "multiple":
                  l = f;
                default:
                  $(e, a, u, f, t, null);
              }
          ((a = o),
            (t = d),
            (e.multiple = !!l),
            a != null ? eu(e, !!l, a, !1) : t != null && eu(e, !!l, t, !0));
          return;
        case "textarea":
          (_("invalid", e), (o = u = l = null));
          for (d in t)
            if (t.hasOwnProperty(d) && ((f = t[d]), f != null))
              switch (d) {
                case "value":
                  l = f;
                  break;
                case "defaultValue":
                  u = f;
                  break;
                case "children":
                  o = f;
                  break;
                case "dangerouslySetInnerHTML":
                  if (f != null) throw Error(v(91));
                  break;
                default:
                  $(e, a, d, f, t, null);
              }
          bm(e, l, u, o);
          return;
        case "option":
          for (n in t)
            t.hasOwnProperty(n) &&
              ((l = t[n]), l != null) &&
              (n === "selected"
                ? (e.selected =
                    l && typeof l != "function" && typeof l != "symbol")
                : $(e, a, n, l, t, null));
          return;
        case "dialog":
          (_("beforetoggle", e), _("toggle", e), _("cancel", e), _("close", e));
          break;
        case "iframe":
        case "object":
          _("load", e);
          break;
        case "video":
        case "audio":
          for (l = 0; l < xo.length; l++) _(xo[l], e);
          break;
        case "image":
          (_("error", e), _("load", e));
          break;
        case "details":
          _("toggle", e);
          break;
        case "embed":
        case "source":
        case "link":
          (_("error", e), _("load", e));
        case "area":
        case "base":
        case "br":
        case "col":
        case "hr":
        case "keygen":
        case "meta":
        case "param":
        case "track":
        case "wbr":
        case "menuitem":
          for (p in t)
            if (t.hasOwnProperty(p) && ((l = t[p]), l != null))
              switch (p) {
                case "children":
                case "dangerouslySetInnerHTML":
                  throw Error(v(137, a));
                default:
                  $(e, a, p, l, t, null);
              }
          return;
        default:
          if (Kn(a)) {
            for (h in t)
              t.hasOwnProperty(h) &&
                ((l = t[h]), l !== void 0 && On(e, a, h, l, t, void 0));
            return;
          }
      }
      for (f in t)
        t.hasOwnProperty(f) &&
          ((l = t[f]), l != null && $(e, a, f, l, t, null));
    }
    function UC(e, a, t, l) {
      switch (a) {
        case "div":
        case "span":
        case "svg":
        case "path":
        case "a":
        case "g":
        case "p":
        case "li":
          break;
        case "input":
          var u = null,
            o = null,
            d = null,
            f = null,
            n = null,
            p = null,
            h = null;
          for (L in t) {
            var x = t[L];
            if (t.hasOwnProperty(L) && x != null)
              switch (L) {
                case "checked":
                  break;
                case "value":
                  break;
                case "defaultValue":
                  n = x;
                default:
                  l.hasOwnProperty(L) || $(e, a, L, null, l, x);
              }
          }
          for (var m in l) {
            var L = l[m];
            if (((x = t[m]), l.hasOwnProperty(m) && (L != null || x != null)))
              switch (m) {
                case "type":
                  o = L;
                  break;
                case "name":
                  u = L;
                  break;
                case "checked":
                  p = L;
                  break;
                case "defaultChecked":
                  h = L;
                  break;
                case "value":
                  d = L;
                  break;
                case "defaultValue":
                  f = L;
                  break;
                case "children":
                case "dangerouslySetInnerHTML":
                  if (L != null) throw Error(v(137, a));
                  break;
                default:
                  L !== x && $(e, a, m, L, l, x);
              }
          }
          un(e, d, f, n, p, h, o, u);
          return;
        case "select":
          L = d = f = m = null;
          for (o in t)
            if (((n = t[o]), t.hasOwnProperty(o) && n != null))
              switch (o) {
                case "value":
                  break;
                case "multiple":
                  L = n;
                default:
                  l.hasOwnProperty(o) || $(e, a, o, null, l, n);
              }
          for (u in l)
            if (
              ((o = l[u]),
              (n = t[u]),
              l.hasOwnProperty(u) && (o != null || n != null))
            )
              switch (u) {
                case "value":
                  m = o;
                  break;
                case "defaultValue":
                  f = o;
                  break;
                case "multiple":
                  d = o;
                default:
                  o !== n && $(e, a, u, o, l, n);
              }
          ((a = f),
            (t = d),
            (l = L),
            m != null
              ? eu(e, !!t, m, !1)
              : !!l != !!t &&
                (a != null ? eu(e, !!t, a, !0) : eu(e, !!t, t ? [] : "", !1)));
          return;
        case "textarea":
          L = m = null;
          for (f in t)
            if (
              ((u = t[f]),
              t.hasOwnProperty(f) && u != null && !l.hasOwnProperty(f))
            )
              switch (f) {
                case "value":
                  break;
                case "children":
                  break;
                default:
                  $(e, a, f, null, l, u);
              }
          for (d in l)
            if (
              ((u = l[d]),
              (o = t[d]),
              l.hasOwnProperty(d) && (u != null || o != null))
            )
              switch (d) {
                case "value":
                  m = u;
                  break;
                case "defaultValue":
                  L = u;
                  break;
                case "children":
                  break;
                case "dangerouslySetInnerHTML":
                  if (u != null) throw Error(v(91));
                  break;
                default:
                  u !== o && $(e, a, d, u, l, o);
              }
          Im(e, m, L);
          return;
        case "option":
          for (var k in t)
            ((m = t[k]),
              t.hasOwnProperty(k) &&
                m != null &&
                !l.hasOwnProperty(k) &&
                (k === "selected"
                  ? (e.selected = !1)
                  : $(e, a, k, null, l, m)));
          for (n in l)
            ((m = l[n]),
              (L = t[n]),
              l.hasOwnProperty(n) &&
                m !== L &&
                (m != null || L != null) &&
                (n === "selected"
                  ? (e.selected =
                      m && typeof m != "function" && typeof m != "symbol")
                  : $(e, a, n, m, l, L)));
          return;
        case "img":
        case "link":
        case "area":
        case "base":
        case "br":
        case "col":
        case "embed":
        case "hr":
        case "keygen":
        case "meta":
        case "param":
        case "source":
        case "track":
        case "wbr":
        case "menuitem":
          for (var w in t)
            ((m = t[w]),
              t.hasOwnProperty(w) &&
                m != null &&
                !l.hasOwnProperty(w) &&
                $(e, a, w, null, l, m));
          for (p in l)
            if (
              ((m = l[p]),
              (L = t[p]),
              l.hasOwnProperty(p) && m !== L && (m != null || L != null))
            )
              switch (p) {
                case "children":
                case "dangerouslySetInnerHTML":
                  if (m != null) throw Error(v(137, a));
                  break;
                default:
                  $(e, a, p, m, l, L);
              }
          return;
        default:
          if (Kn(a)) {
            for (var A in t)
              ((m = t[A]),
                t.hasOwnProperty(A) &&
                  m !== void 0 &&
                  !l.hasOwnProperty(A) &&
                  On(e, a, A, void 0, l, m));
            for (h in l)
              ((m = l[h]),
                (L = t[h]),
                !l.hasOwnProperty(h) ||
                  m === L ||
                  (m === void 0 && L === void 0) ||
                  On(e, a, h, m, l, L));
            return;
          }
      }
      for (var c in t)
        ((m = t[c]),
          t.hasOwnProperty(c) &&
            m != null &&
            !l.hasOwnProperty(c) &&
            $(e, a, c, null, l, m));
      for (x in l)
        ((m = l[x]),
          (L = t[x]),
          !l.hasOwnProperty(x) ||
            m === L ||
            (m == null && L == null) ||
            $(e, a, x, m, l, L));
    }
    function Hc(e) {
      switch (e) {
        case "css":
        case "script":
        case "font":
        case "img":
        case "image":
        case "input":
        case "link":
          return !0;
        default:
          return !1;
      }
    }
    function HC() {
      if (typeof performance.getEntriesByType == "function") {
        for (
          var e = 0, a = 0, t = performance.getEntriesByType("resource"), l = 0;
          l < t.length;
          l++
        ) {
          var u = t[l],
            o = u.transferSize,
            d = u.initiatorType,
            f = u.duration;
          if (o && f && Hc(d)) {
            for (d = 0, f = u.responseEnd, l += 1; l < t.length; l++) {
              var n = t[l],
                p = n.startTime;
              if (p > f) break;
              var h = n.transferSize,
                x = n.initiatorType;
              h &&
                Hc(x) &&
                ((n = n.responseEnd),
                (d += h * (n < f ? 1 : (f - p) / (n - p))));
            }
            if ((--l, (a += (8 * (o + d)) / (u.duration / 1e3)), e++, 10 < e))
              break;
          }
        }
        if (0 < e) return a / e / 1e6;
      }
      return navigator.connection &&
        ((e = navigator.connection.downlink), typeof e == "number")
        ? e
        : 5;
    }
    var En = null,
      qn = null;
    function ms(e) {
      return e.nodeType === 9 ? e : e.ownerDocument;
    }
    function Fc(e) {
      switch (e) {
        case "http://www.w3.org/2000/svg":
          return 1;
        case "http://www.w3.org/1998/Math/MathML":
          return 2;
        default:
          return 0;
      }
    }
    function Dh(e, a) {
      if (e === 0)
        switch (a) {
          case "svg":
            return 1;
          case "math":
            return 2;
          default:
            return 0;
        }
      return e === 1 && a === "foreignObject" ? 0 : e;
    }
    function Nn(e, a) {
      return (
        e === "textarea" ||
        e === "noscript" ||
        typeof a.children == "string" ||
        typeof a.children == "number" ||
        typeof a.children == "bigint" ||
        (typeof a.dangerouslySetInnerHTML == "object" &&
          a.dangerouslySetInnerHTML !== null &&
          a.dangerouslySetInnerHTML.__html != null)
      );
    }
    var Yf = null;
    function FC() {
      var e = window.event;
      return e && e.type === "popstate"
        ? e === Yf
          ? !1
          : ((Yf = e), !0)
        : ((Yf = null), !1);
    }
    var Th = typeof setTimeout == "function" ? setTimeout : void 0,
      PC = typeof clearTimeout == "function" ? clearTimeout : void 0,
      Pc = typeof Promise == "function" ? Promise : void 0,
      _C =
        typeof queueMicrotask == "function"
          ? queueMicrotask
          : typeof Pc < "u"
            ? function (e) {
                return Pc.resolve(null).then(e).catch(GC);
              }
            : Th;
    function GC(e) {
      setTimeout(function () {
        throw e;
      });
    }
    function Ft(e) {
      return e === "head";
    }
    function _c(e, a) {
      var t = a,
        l = 0;
      do {
        var u = t.nextSibling;
        if ((e.removeChild(t), u && u.nodeType === 8))
          if (((t = u.data), t === "/$" || t === "/&")) {
            if (l === 0) {
              (e.removeChild(u), Lu(a));
              return;
            }
            l--;
          } else if (
            t === "$" ||
            t === "$?" ||
            t === "$~" ||
            t === "$!" ||
            t === "&"
          )
            l++;
          else if (t === "html") fo(e.ownerDocument.documentElement);
          else if (t === "head") {
            ((t = e.ownerDocument.head), fo(t));
            for (var o = t.firstChild; o; ) {
              var d = o.nextSibling,
                f = o.nodeName;
              (o[Mo] ||
                f === "SCRIPT" ||
                f === "STYLE" ||
                (f === "LINK" && o.rel.toLowerCase() === "stylesheet") ||
                t.removeChild(o),
                (o = d));
            }
          } else t === "body" && fo(e.ownerDocument.body);
        t = u;
      } while (t);
      Lu(a);
    }
    function Gc(e, a) {
      var t = e;
      e = 0;
      do {
        var l = t.nextSibling;
        if (
          (t.nodeType === 1
            ? a
              ? ((t._stashedDisplay = t.style.display),
                (t.style.display = "none"))
              : ((t.style.display = t._stashedDisplay || ""),
                t.getAttribute("style") === "" && t.removeAttribute("style"))
            : t.nodeType === 3 &&
              (a
                ? ((t._stashedText = t.nodeValue), (t.nodeValue = ""))
                : (t.nodeValue = t._stashedText || "")),
          l && l.nodeType === 8)
        )
          if (((t = l.data), t === "/$")) {
            if (e === 0) break;
            e--;
          } else (t !== "$" && t !== "$?" && t !== "$~" && t !== "$!") || e++;
        t = l;
      } while (t);
    }
    function Un(e) {
      var a = e.firstChild;
      for (a && a.nodeType === 10 && (a = a.nextSibling); a; ) {
        var t = a;
        switch (((a = a.nextSibling), t.nodeName)) {
          case "HTML":
          case "HEAD":
          case "BODY":
            (Un(t), Qn(t));
            continue;
          case "SCRIPT":
          case "STYLE":
            continue;
          case "LINK":
            if (t.rel.toLowerCase() === "stylesheet") continue;
        }
        e.removeChild(t);
      }
    }
    function VC(e, a, t, l) {
      for (; e.nodeType === 1; ) {
        var u = t;
        if (e.nodeName.toLowerCase() !== a.toLowerCase()) {
          if (!l && (e.nodeName !== "INPUT" || e.type !== "hidden")) break;
        } else if (l) {
          if (!e[Mo])
            switch (a) {
              case "meta":
                if (!e.hasAttribute("itemprop")) break;
                return e;
              case "link":
                if (
                  ((o = e.getAttribute("rel")),
                  o === "stylesheet" && e.hasAttribute("data-precedence"))
                )
                  break;
                if (
                  o !== u.rel ||
                  e.getAttribute("href") !==
                    (u.href == null || u.href === "" ? null : u.href) ||
                  e.getAttribute("crossorigin") !==
                    (u.crossOrigin == null ? null : u.crossOrigin) ||
                  e.getAttribute("title") !== (u.title == null ? null : u.title)
                )
                  break;
                return e;
              case "style":
                if (e.hasAttribute("data-precedence")) break;
                return e;
              case "script":
                if (
                  ((o = e.getAttribute("src")),
                  (o !== (u.src == null ? null : u.src) ||
                    e.getAttribute("type") !==
                      (u.type == null ? null : u.type) ||
                    e.getAttribute("crossorigin") !==
                      (u.crossOrigin == null ? null : u.crossOrigin)) &&
                    o &&
                    e.hasAttribute("async") &&
                    !e.hasAttribute("itemprop"))
                )
                  break;
                return e;
              default:
                return e;
            }
        } else if (a === "input" && e.type === "hidden") {
          var o = u.name == null ? null : "" + u.name;
          if (u.type === "hidden" && e.getAttribute("name") === o) return e;
        } else return e;
        if (((e = ga(e.nextSibling)), e === null)) break;
      }
      return null;
    }
    function XC(e, a, t) {
      if (a === "") return null;
      for (; e.nodeType !== 3; )
        if (
          ((e.nodeType !== 1 ||
            e.nodeName !== "INPUT" ||
            e.type !== "hidden") &&
            !t) ||
          ((e = ga(e.nextSibling)), e === null)
        )
          return null;
      return e;
    }
    function Bh(e, a) {
      for (; e.nodeType !== 8; )
        if (
          ((e.nodeType !== 1 ||
            e.nodeName !== "INPUT" ||
            e.type !== "hidden") &&
            !a) ||
          ((e = ga(e.nextSibling)), e === null)
        )
          return null;
      return e;
    }
    function Hn(e) {
      return e.data === "$?" || e.data === "$~";
    }
    function Fn(e) {
      return (
        e.data === "$!" ||
        (e.data === "$?" && e.ownerDocument.readyState !== "loading")
      );
    }
    function YC(e, a) {
      var t = e.ownerDocument;
      if (e.data === "$~") e._reactRetry = a;
      else if (e.data !== "$?" || t.readyState !== "loading") a();
      else {
        var l = function () {
          (a(), t.removeEventListener("DOMContentLoaded", l));
        };
        (t.addEventListener("DOMContentLoaded", l), (e._reactRetry = l));
      }
    }
    function ga(e) {
      for (; e != null; e = e.nextSibling) {
        var a = e.nodeType;
        if (a === 1 || a === 3) break;
        if (a === 8) {
          if (
            ((a = e.data),
            a === "$" ||
              a === "$!" ||
              a === "$?" ||
              a === "$~" ||
              a === "&" ||
              a === "F!" ||
              a === "F")
          )
            break;
          if (a === "/$" || a === "/&") return null;
        }
      }
      return e;
    }
    var Pn = null;
    function Vc(e) {
      e = e.nextSibling;
      for (var a = 0; e; ) {
        if (e.nodeType === 8) {
          var t = e.data;
          if (t === "/$" || t === "/&") {
            if (a === 0) return ga(e.nextSibling);
            a--;
          } else
            (t !== "$" &&
              t !== "$!" &&
              t !== "$?" &&
              t !== "$~" &&
              t !== "&") ||
              a++;
        }
        e = e.nextSibling;
      }
      return null;
    }
    function Xc(e) {
      e = e.previousSibling;
      for (var a = 0; e; ) {
        if (e.nodeType === 8) {
          var t = e.data;
          if (
            t === "$" ||
            t === "$!" ||
            t === "$?" ||
            t === "$~" ||
            t === "&"
          ) {
            if (a === 0) return e;
            a--;
          } else (t !== "/$" && t !== "/&") || a++;
        }
        e = e.previousSibling;
      }
      return null;
    }
    function zh(e, a, t) {
      switch (((a = ms(t)), e)) {
        case "html":
          if (((e = a.documentElement), !e)) throw Error(v(452));
          return e;
        case "head":
          if (((e = a.head), !e)) throw Error(v(453));
          return e;
        case "body":
          if (((e = a.body), !e)) throw Error(v(454));
          return e;
        default:
          throw Error(v(451));
      }
    }
    function fo(e) {
      for (var a = e.attributes; a.length; ) e.removeAttributeNode(a[0]);
      Qn(e);
    }
    var xa = new Map(),
      Yc = new Set();
    function ps(e) {
      return typeof e.getRootNode == "function"
        ? e.getRootNode()
        : e.nodeType === 9
          ? e
          : e.ownerDocument;
    }
    var ft = K.d;
    K.d = { f: jC, r: ZC, D: QC, C: KC, L: JC, m: WC, X: ey, S: $C, M: ay };
    function jC() {
      var e = ft.f(),
        a = Bs();
      return e || a;
    }
    function ZC(e) {
      var a = xu(e);
      a !== null && a.tag === 5 && a.type === "form" ? Ap(a) : ft.r(e);
    }
    var vu = typeof document > "u" ? null : document;
    function Rh(e, a, t) {
      var l = vu;
      if (l && typeof a == "string" && a) {
        var u = ma(a);
        ((u = 'link[rel="' + e + '"][href="' + u + '"]'),
          typeof t == "string" && (u += '[crossorigin="' + t + '"]'),
          Yc.has(u) ||
            (Yc.add(u),
            (e = { rel: e, crossOrigin: t, href: a }),
            l.querySelector(u) === null &&
              ((a = l.createElement("link")),
              we(a, "link", e),
              ve(a),
              l.head.appendChild(a))));
      }
    }
    function QC(e) {
      (ft.D(e), Rh("dns-prefetch", e, null));
    }
    function KC(e, a) {
      (ft.C(e, a), Rh("preconnect", e, a));
    }
    function JC(e, a, t) {
      ft.L(e, a, t);
      var l = vu;
      if (l && e && a) {
        var u = 'link[rel="preload"][as="' + ma(a) + '"]';
        a === "image" && t && t.imageSrcSet
          ? ((u += '[imagesrcset="' + ma(t.imageSrcSet) + '"]'),
            typeof t.imageSizes == "string" &&
              (u += '[imagesizes="' + ma(t.imageSizes) + '"]'))
          : (u += '[href="' + ma(e) + '"]');
        var o = u;
        switch (a) {
          case "style":
            o = hu(e);
            break;
          case "script":
            o = Iu(e);
        }
        xa.has(o) ||
          ((e = de(
            {
              rel: "preload",
              href: a === "image" && t && t.imageSrcSet ? void 0 : e,
              as: a,
            },
            t,
          )),
          xa.set(o, e),
          l.querySelector(u) !== null ||
            (a === "style" && l.querySelector(Ro(o))) ||
            (a === "script" && l.querySelector(Oo(o))) ||
            ((a = l.createElement("link")),
            we(a, "link", e),
            ve(a),
            l.head.appendChild(a)));
      }
    }
    function WC(e, a) {
      ft.m(e, a);
      var t = vu;
      if (t && e) {
        var l = a && typeof a.as == "string" ? a.as : "script",
          u =
            'link[rel="modulepreload"][as="' +
            ma(l) +
            '"][href="' +
            ma(e) +
            '"]',
          o = u;
        switch (l) {
          case "audioworklet":
          case "paintworklet":
          case "serviceworker":
          case "sharedworker":
          case "worker":
          case "script":
            o = Iu(e);
        }
        if (
          !xa.has(o) &&
          ((e = de({ rel: "modulepreload", href: e }, a)),
          xa.set(o, e),
          t.querySelector(u) === null)
        ) {
          switch (l) {
            case "audioworklet":
            case "paintworklet":
            case "serviceworker":
            case "sharedworker":
            case "worker":
            case "script":
              if (t.querySelector(Oo(o))) return;
          }
          ((l = t.createElement("link")),
            we(l, "link", e),
            ve(l),
            t.head.appendChild(l));
        }
      }
    }
    function $C(e, a, t) {
      ft.S(e, a, t);
      var l = vu;
      if (l && e) {
        var u = $l(l).hoistableStyles,
          o = hu(e);
        a = a || "default";
        var d = u.get(o);
        if (!d) {
          var f = { loading: 0, preload: null };
          if ((d = l.querySelector(Ro(o)))) f.loading = 5;
          else {
            ((e = de({ rel: "stylesheet", href: e, "data-precedence": a }, t)),
              (t = xa.get(o)) && Oi(e, t));
            var n = (d = l.createElement("link"));
            (ve(n),
              we(n, "link", e),
              (n._p = new Promise(function (p, h) {
                ((n.onload = p), (n.onerror = h));
              })),
              n.addEventListener("load", function () {
                f.loading |= 1;
              }),
              n.addEventListener("error", function () {
                f.loading |= 2;
              }),
              (f.loading |= 4),
              Fd(d, a, l));
          }
          ((d = { type: "stylesheet", instance: d, count: 1, state: f }),
            u.set(o, d));
        }
      }
    }
    function ey(e, a) {
      ft.X(e, a);
      var t = vu;
      if (t && e) {
        var l = $l(t).hoistableScripts,
          u = Iu(e),
          o = l.get(u);
        o ||
          ((o = t.querySelector(Oo(u))),
          o ||
            ((e = de({ src: e, async: !0 }, a)),
            (a = xa.get(u)) && Ei(e, a),
            (o = t.createElement("script")),
            ve(o),
            we(o, "link", e),
            t.head.appendChild(o)),
          (o = { type: "script", instance: o, count: 1, state: null }),
          l.set(u, o));
      }
    }
    function ay(e, a) {
      ft.M(e, a);
      var t = vu;
      if (t && e) {
        var l = $l(t).hoistableScripts,
          u = Iu(e),
          o = l.get(u);
        o ||
          ((o = t.querySelector(Oo(u))),
          o ||
            ((e = de({ src: e, async: !0, type: "module" }, a)),
            (a = xa.get(u)) && Ei(e, a),
            (o = t.createElement("script")),
            ve(o),
            we(o, "link", e),
            t.head.appendChild(o)),
          (o = { type: "script", instance: o, count: 1, state: null }),
          l.set(u, o));
      }
    }
    function jc(e, a, t, l) {
      var u = (u = kt.current) ? ps(u) : null;
      if (!u) throw Error(v(446));
      switch (e) {
        case "meta":
        case "title":
          return null;
        case "style":
          return typeof t.precedence == "string" && typeof t.href == "string"
            ? ((a = hu(t.href)),
              (t = $l(u).hoistableStyles),
              (l = t.get(a)),
              l ||
                ((l = { type: "style", instance: null, count: 0, state: null }),
                t.set(a, l)),
              l)
            : { type: "void", instance: null, count: 0, state: null };
        case "link":
          if (
            t.rel === "stylesheet" &&
            typeof t.href == "string" &&
            typeof t.precedence == "string"
          ) {
            e = hu(t.href);
            var o = $l(u).hoistableStyles,
              d = o.get(e);
            if (
              (d ||
                ((u = u.ownerDocument || u),
                (d = {
                  type: "stylesheet",
                  instance: null,
                  count: 0,
                  state: { loading: 0, preload: null },
                }),
                o.set(e, d),
                (o = u.querySelector(Ro(e))) &&
                  !o._p &&
                  ((d.instance = o), (d.state.loading = 5)),
                xa.has(e) ||
                  ((t = {
                    rel: "preload",
                    as: "style",
                    href: t.href,
                    crossOrigin: t.crossOrigin,
                    integrity: t.integrity,
                    media: t.media,
                    hrefLang: t.hrefLang,
                    referrerPolicy: t.referrerPolicy,
                  }),
                  xa.set(e, t),
                  o || ty(u, e, t, d.state))),
              a && l === null)
            )
              throw Error(v(528, ""));
            return d;
          }
          if (a && l !== null) throw Error(v(529, ""));
          return null;
        case "script":
          return (
            (a = t.async),
            (t = t.src),
            typeof t == "string" &&
            a &&
            typeof a != "function" &&
            typeof a != "symbol"
              ? ((a = Iu(t)),
                (t = $l(u).hoistableScripts),
                (l = t.get(a)),
                l ||
                  ((l = {
                    type: "script",
                    instance: null,
                    count: 0,
                    state: null,
                  }),
                  t.set(a, l)),
                l)
              : { type: "void", instance: null, count: 0, state: null }
          );
        default:
          throw Error(v(444, e));
      }
    }
    function hu(e) {
      return 'href="' + ma(e) + '"';
    }
    function Ro(e) {
      return 'link[rel="stylesheet"][' + e + "]";
    }
    function Oh(e) {
      return de({}, e, { "data-precedence": e.precedence, precedence: null });
    }
    function ty(e, a, t, l) {
      e.querySelector('link[rel="preload"][as="style"][' + a + "]")
        ? (l.loading = 1)
        : ((a = e.createElement("link")),
          (l.preload = a),
          a.addEventListener("load", function () {
            return (l.loading |= 1);
          }),
          a.addEventListener("error", function () {
            return (l.loading |= 2);
          }),
          we(a, "link", t),
          ve(a),
          e.head.appendChild(a));
    }
    function Iu(e) {
      return '[src="' + ma(e) + '"]';
    }
    function Oo(e) {
      return "script[async]" + e;
    }
    function Zc(e, a, t) {
      if ((a.count++, a.instance === null))
        switch (a.type) {
          case "style":
            var l = e.querySelector('style[data-href~="' + ma(t.href) + '"]');
            if (l) return ((a.instance = l), ve(l), l);
            var u = de({}, t, {
              "data-href": t.href,
              "data-precedence": t.precedence,
              href: null,
              precedence: null,
            });
            return (
              (l = (e.ownerDocument || e).createElement("style")),
              ve(l),
              we(l, "style", u),
              Fd(l, t.precedence, e),
              (a.instance = l)
            );
          case "stylesheet":
            u = hu(t.href);
            var o = e.querySelector(Ro(u));
            if (o) return ((a.state.loading |= 4), (a.instance = o), ve(o), o);
            ((l = Oh(t)),
              (u = xa.get(u)) && Oi(l, u),
              (o = (e.ownerDocument || e).createElement("link")),
              ve(o));
            var d = o;
            return (
              (d._p = new Promise(function (f, n) {
                ((d.onload = f), (d.onerror = n));
              })),
              we(o, "link", l),
              (a.state.loading |= 4),
              Fd(o, t.precedence, e),
              (a.instance = o)
            );
          case "script":
            return (
              (o = Iu(t.src)),
              (u = e.querySelector(Oo(o)))
                ? ((a.instance = u), ve(u), u)
                : ((l = t),
                  (u = xa.get(o)) && ((l = de({}, t)), Ei(l, u)),
                  (e = e.ownerDocument || e),
                  (u = e.createElement("script")),
                  ve(u),
                  we(u, "link", l),
                  e.head.appendChild(u),
                  (a.instance = u))
            );
          case "void":
            return null;
          default:
            throw Error(v(443, a.type));
        }
      else
        a.type === "stylesheet" &&
          (a.state.loading & 4) === 0 &&
          ((l = a.instance), (a.state.loading |= 4), Fd(l, t.precedence, e));
      return a.instance;
    }
    function Fd(e, a, t) {
      for (
        var l = t.querySelectorAll(
            'link[rel="stylesheet"][data-precedence],style[data-precedence]',
          ),
          u = l.length ? l[l.length - 1] : null,
          o = u,
          d = 0;
        d < l.length;
        d++
      ) {
        var f = l[d];
        if (f.dataset.precedence === a) o = f;
        else if (o !== u) break;
      }
      o
        ? o.parentNode.insertBefore(e, o.nextSibling)
        : ((a = t.nodeType === 9 ? t.head : t),
          a.insertBefore(e, a.firstChild));
    }
    function Oi(e, a) {
      (e.crossOrigin == null && (e.crossOrigin = a.crossOrigin),
        e.referrerPolicy == null && (e.referrerPolicy = a.referrerPolicy),
        e.title == null && (e.title = a.title));
    }
    function Ei(e, a) {
      (e.crossOrigin == null && (e.crossOrigin = a.crossOrigin),
        e.referrerPolicy == null && (e.referrerPolicy = a.referrerPolicy),
        e.integrity == null && (e.integrity = a.integrity));
    }
    var Pd = null;
    function Qc(e, a, t) {
      if (Pd === null) {
        var l = new Map(),
          u = (Pd = new Map());
        u.set(t, l);
      } else ((u = Pd), (l = u.get(t)), l || ((l = new Map()), u.set(t, l)));
      if (l.has(e)) return l;
      for (
        l.set(e, null), t = t.getElementsByTagName(e), u = 0;
        u < t.length;
        u++
      ) {
        var o = t[u];
        if (
          !(
            o[Mo] ||
            o[Ae] ||
            (e === "link" && o.getAttribute("rel") === "stylesheet")
          ) &&
          o.namespaceURI !== "http://www.w3.org/2000/svg"
        ) {
          var d = o.getAttribute(a) || "";
          d = e + d;
          var f = l.get(d);
          f ? f.push(o) : l.set(d, [o]);
        }
      }
      return l;
    }
    function Kc(e, a, t) {
      ((e = e.ownerDocument || e),
        e.head.insertBefore(
          t,
          a === "title" ? e.querySelector("head > title") : null,
        ));
    }
    function ly(e, a, t) {
      if (t === 1 || a.itemProp != null) return !1;
      switch (e) {
        case "meta":
        case "title":
          return !0;
        case "style":
          if (
            typeof a.precedence != "string" ||
            typeof a.href != "string" ||
            a.href === ""
          )
            break;
          return !0;
        case "link":
          if (
            typeof a.rel != "string" ||
            typeof a.href != "string" ||
            a.href === "" ||
            a.onLoad ||
            a.onError
          )
            break;
          return a.rel === "stylesheet"
            ? ((e = a.disabled), typeof a.precedence == "string" && e == null)
            : !0;
        case "script":
          if (
            a.async &&
            typeof a.async != "function" &&
            typeof a.async != "symbol" &&
            !a.onLoad &&
            !a.onError &&
            a.src &&
            typeof a.src == "string"
          )
            return !0;
      }
      return !1;
    }
    function Eh(e) {
      return !(e.type === "stylesheet" && (e.state.loading & 3) === 0);
    }
    function uy(e, a, t, l) {
      if (
        t.type === "stylesheet" &&
        (typeof l.media != "string" || matchMedia(l.media).matches !== !1) &&
        (t.state.loading & 4) === 0
      ) {
        if (t.instance === null) {
          var u = hu(l.href),
            o = a.querySelector(Ro(u));
          if (o) {
            ((a = o._p),
              a !== null &&
                typeof a == "object" &&
                typeof a.then == "function" &&
                (e.count++, (e = hs.bind(e)), a.then(e, e)),
              (t.state.loading |= 4),
              (t.instance = o),
              ve(o));
            return;
          }
          ((o = a.ownerDocument || a),
            (l = Oh(l)),
            (u = xa.get(u)) && Oi(l, u),
            (o = o.createElement("link")),
            ve(o));
          var d = o;
          ((d._p = new Promise(function (f, n) {
            ((d.onload = f), (d.onerror = n));
          })),
            we(o, "link", l),
            (t.instance = o));
        }
        (e.stylesheets === null && (e.stylesheets = new Map()),
          e.stylesheets.set(t, a),
          (a = t.state.preload) &&
            (t.state.loading & 3) === 0 &&
            (e.count++,
            (t = hs.bind(e)),
            a.addEventListener("load", t),
            a.addEventListener("error", t)));
      }
    }
    var jf = 0;
    function oy(e, a) {
      return (
        e.stylesheets && e.count === 0 && _d(e, e.stylesheets),
        0 < e.count || 0 < e.imgCount
          ? function (t) {
              var l = setTimeout(function () {
                if ((e.stylesheets && _d(e, e.stylesheets), e.unsuspend)) {
                  var o = e.unsuspend;
                  ((e.unsuspend = null), o());
                }
              }, 6e4 + a);
              0 < e.imgBytes && jf === 0 && (jf = 62500 * HC());
              var u = setTimeout(
                function () {
                  if (
                    ((e.waitingForImages = !1),
                    e.count === 0 &&
                      (e.stylesheets && _d(e, e.stylesheets), e.unsuspend))
                  ) {
                    var o = e.unsuspend;
                    ((e.unsuspend = null), o());
                  }
                },
                (e.imgBytes > jf ? 50 : 800) + a,
              );
              return (
                (e.unsuspend = t),
                function () {
                  ((e.unsuspend = null), clearTimeout(l), clearTimeout(u));
                }
              );
            }
          : null
      );
    }
    function hs() {
      if (
        (this.count--,
        this.count === 0 && (this.imgCount === 0 || !this.waitingForImages))
      ) {
        if (this.stylesheets) _d(this, this.stylesheets);
        else if (this.unsuspend) {
          var e = this.unsuspend;
          ((this.unsuspend = null), e());
        }
      }
    }
    var Ls = null;
    function _d(e, a) {
      ((e.stylesheets = null),
        e.unsuspend !== null &&
          (e.count++,
          (Ls = new Map()),
          a.forEach(dy, e),
          (Ls = null),
          hs.call(e)));
    }
    function dy(e, a) {
      if (!(a.state.loading & 4)) {
        var t = Ls.get(e);
        if (t) var l = t.get(null);
        else {
          ((t = new Map()), Ls.set(e, t));
          for (
            var u = e.querySelectorAll(
                "link[data-precedence],style[data-precedence]",
              ),
              o = 0;
            o < u.length;
            o++
          ) {
            var d = u[o];
            (d.nodeName === "LINK" || d.getAttribute("media") !== "not all") &&
              (t.set(d.dataset.precedence, d), (l = d));
          }
          l && t.set(null, l);
        }
        ((u = a.instance),
          (d = u.getAttribute("data-precedence")),
          (o = t.get(d) || l),
          o === l && t.set(null, u),
          t.set(d, u),
          this.count++,
          (l = hs.bind(this)),
          u.addEventListener("load", l),
          u.addEventListener("error", l),
          o
            ? o.parentNode.insertBefore(u, o.nextSibling)
            : ((e = e.nodeType === 9 ? e.head : e),
              e.insertBefore(u, e.firstChild)),
          (a.state.loading |= 4));
      }
    }
    var yo = {
      $$typeof: Ja,
      Provider: null,
      Consumer: null,
      _currentValue: nl,
      _currentValue2: nl,
      _threadCount: 0,
    };
    function sy(e, a, t, l, u, o, d, f, n) {
      ((this.tag = 1),
        (this.containerInfo = e),
        (this.pingCache = this.current = this.pendingChildren = null),
        (this.timeoutHandle = -1),
        (this.callbackNode =
          this.next =
          this.pendingContext =
          this.context =
          this.cancelPendingCommit =
            null),
        (this.callbackPriority = 0),
        (this.expirationTimes = Cf(-1)),
        (this.entangledLanes =
          this.shellSuspendCounter =
          this.errorRecoveryDisabledLanes =
          this.expiredLanes =
          this.warmLanes =
          this.pingedLanes =
          this.suspendedLanes =
          this.pendingLanes =
            0),
        (this.entanglements = Cf(0)),
        (this.hiddenUpdates = Cf(null)),
        (this.identifierPrefix = l),
        (this.onUncaughtError = u),
        (this.onCaughtError = o),
        (this.onRecoverableError = d),
        (this.pooledCache = null),
        (this.pooledCacheLanes = 0),
        (this.formState = n),
        (this.incompleteTransitions = new Map()));
    }
    function qh(e, a, t, l, u, o, d, f, n, p, h, x) {
      return (
        (e = new sy(e, a, t, d, n, p, h, x, f)),
        (a = 1),
        o === !0 && (a |= 24),
        (o = Qe(3, null, null, a)),
        (e.current = o),
        (o.stateNode = e),
        (a = si()),
        a.refCount++,
        (e.pooledCache = a),
        a.refCount++,
        (o.memoizedState = { element: l, isDehydrated: t, cache: a }),
        ii(o),
        e
      );
    }
    function Nh(e) {
      return e ? ((e = Ql), e) : Ql;
    }
    function Uh(e, a, t, l, u, o) {
      ((u = Nh(u)),
        l.context === null ? (l.context = u) : (l.pendingContext = u),
        (l = wt(a)),
        (l.payload = { element: t }),
        (o = o === void 0 ? null : o),
        o !== null && (l.callback = o),
        (t = Dt(e, l, a)),
        t !== null && (_e(t, e, a), $u(t, e, a)));
    }
    function Jc(e, a) {
      if (((e = e.memoizedState), e !== null && e.dehydrated !== null)) {
        var t = e.retryLane;
        e.retryLane = t !== 0 && t < a ? t : a;
      }
    }
    function qi(e, a) {
      (Jc(e, a), (e = e.alternate) && Jc(e, a));
    }
    function Hh(e) {
      if (e.tag === 13 || e.tag === 31) {
        var a = vl(e, 67108864);
        (a !== null && _e(a, e, 67108864), qi(e, 67108864));
      }
    }
    function Wc(e) {
      if (e.tag === 13 || e.tag === 31) {
        var a = ea();
        a = jn(a);
        var t = vl(e, a);
        (t !== null && _e(t, e, a), qi(e, a));
      }
    }
    var gs = !0;
    function fy(e, a, t, l) {
      var u = R.T;
      R.T = null;
      var o = K.p;
      try {
        ((K.p = 2), Ni(e, a, t, l));
      } finally {
        ((K.p = o), (R.T = u));
      }
    }
    function ny(e, a, t, l) {
      var u = R.T;
      R.T = null;
      var o = K.p;
      try {
        ((K.p = 8), Ni(e, a, t, l));
      } finally {
        ((K.p = o), (R.T = u));
      }
    }
    function Ni(e, a, t, l) {
      if (gs) {
        var u = _n(l);
        if (u === null) (Xf(e, a, l, xs, t), $c(e, l));
        else if (ry(u, e, a, t, l)) l.stopPropagation();
        else if (($c(e, l), a & 4 && -1 < iy.indexOf(e))) {
          for (; u !== null; ) {
            var o = xu(u);
            if (o !== null)
              switch (o.tag) {
                case 3:
                  if (
                    ((o = o.stateNode), o.current.memoizedState.isDehydrated)
                  ) {
                    var d = dl(o.pendingLanes);
                    if (d !== 0) {
                      var f = o;
                      for (f.pendingLanes |= 2, f.entangledLanes |= 2; d; ) {
                        var n = 1 << (31 - $e(d));
                        ((f.entanglements[1] |= n), (d &= ~n));
                      }
                      (qa(o), (Q & 6) === 0 && ((ss = Je() + 500), zo(0, !1)));
                    }
                  }
                  break;
                case 31:
                case 13:
                  ((f = vl(o, 2)), f !== null && _e(f, o, 2), Bs(), qi(o, 2));
              }
            if (((o = _n(l)), o === null && Xf(e, a, l, xs, t), o === u)) break;
            u = o;
          }
          u !== null && l.stopPropagation();
        } else Xf(e, a, l, null, t);
      }
    }
    function _n(e) {
      return ((e = Jn(e)), Ui(e));
    }
    var xs = null;
    function Ui(e) {
      if (((xs = null), (e = Gl(e)), e !== null)) {
        var a = Io(e);
        if (a === null) e = null;
        else {
          var t = a.tag;
          if (t === 13) {
            if (((e = om(a)), e !== null)) return e;
            e = null;
          } else if (t === 31) {
            if (((e = dm(a)), e !== null)) return e;
            e = null;
          } else if (t === 3) {
            if (a.stateNode.current.memoizedState.isDehydrated)
              return a.tag === 3 ? a.stateNode.containerInfo : null;
            e = null;
          } else a !== e && (e = null);
        }
      }
      return ((xs = e), null);
    }
    function Fh(e) {
      switch (e) {
        case "beforetoggle":
        case "cancel":
        case "click":
        case "close":
        case "contextmenu":
        case "copy":
        case "cut":
        case "auxclick":
        case "dblclick":
        case "dragend":
        case "dragstart":
        case "drop":
        case "focusin":
        case "focusout":
        case "input":
        case "invalid":
        case "keydown":
        case "keypress":
        case "keyup":
        case "mousedown":
        case "mouseup":
        case "paste":
        case "pause":
        case "play":
        case "pointercancel":
        case "pointerdown":
        case "pointerup":
        case "ratechange":
        case "reset":
        case "resize":
        case "seeked":
        case "submit":
        case "toggle":
        case "touchcancel":
        case "touchend":
        case "touchstart":
        case "volumechange":
        case "change":
        case "selectionchange":
        case "textInput":
        case "compositionstart":
        case "compositionend":
        case "compositionupdate":
        case "beforeblur":
        case "afterblur":
        case "beforeinput":
        case "blur":
        case "fullscreenchange":
        case "focus":
        case "hashchange":
        case "popstate":
        case "select":
        case "selectstart":
          return 2;
        case "drag":
        case "dragenter":
        case "dragexit":
        case "dragleave":
        case "dragover":
        case "mousemove":
        case "mouseout":
        case "mouseover":
        case "pointermove":
        case "pointerout":
        case "pointerover":
        case "scroll":
        case "touchmove":
        case "wheel":
        case "mouseenter":
        case "mouseleave":
        case "pointerenter":
        case "pointerleave":
          return 8;
        case "message":
          switch (Jg()) {
            case im:
              return 2;
            case rm:
              return 8;
            case jd:
            case Wg:
              return 32;
            case cm:
              return 268435456;
            default:
              return 32;
          }
        default:
          return 32;
      }
    }
    var Gn = !1,
      zt = null,
      Rt = null,
      Ot = null,
      So = new Map(),
      vo = new Map(),
      yt = [],
      iy =
        "mousedown mouseup touchcancel touchend touchstart auxclick dblclick pointercancel pointerdown pointerup dragend dragstart drop compositionend compositionstart keydown keypress keyup input textInput copy cut paste click change contextmenu reset".split(
          " ",
        );
    function $c(e, a) {
      switch (e) {
        case "focusin":
        case "focusout":
          zt = null;
          break;
        case "dragenter":
        case "dragleave":
          Rt = null;
          break;
        case "mouseover":
        case "mouseout":
          Ot = null;
          break;
        case "pointerover":
        case "pointerout":
          So.delete(a.pointerId);
          break;
        case "gotpointercapture":
        case "lostpointercapture":
          vo.delete(a.pointerId);
      }
    }
    function _u(e, a, t, l, u, o) {
      return e === null || e.nativeEvent !== o
        ? ((e = {
            blockedOn: a,
            domEventName: t,
            eventSystemFlags: l,
            nativeEvent: o,
            targetContainers: [u],
          }),
          a !== null && ((a = xu(a)), a !== null && Hh(a)),
          e)
        : ((e.eventSystemFlags |= l),
          (a = e.targetContainers),
          u !== null && a.indexOf(u) === -1 && a.push(u),
          e);
    }
    function ry(e, a, t, l, u) {
      switch (a) {
        case "focusin":
          return ((zt = _u(zt, e, a, t, l, u)), !0);
        case "dragenter":
          return ((Rt = _u(Rt, e, a, t, l, u)), !0);
        case "mouseover":
          return ((Ot = _u(Ot, e, a, t, l, u)), !0);
        case "pointerover":
          var o = u.pointerId;
          return (So.set(o, _u(So.get(o) || null, e, a, t, l, u)), !0);
        case "gotpointercapture":
          return (
            (o = u.pointerId),
            vo.set(o, _u(vo.get(o) || null, e, a, t, l, u)),
            !0
          );
      }
      return !1;
    }
    function Ph(e) {
      var a = Gl(e.target);
      if (a !== null) {
        var t = Io(a);
        if (t !== null) {
          if (((a = t.tag), a === 13)) {
            if (((a = om(t)), a !== null)) {
              ((e.blockedOn = a),
                qr(e.priority, function () {
                  Wc(t);
                }));
              return;
            }
          } else if (a === 31) {
            if (((a = dm(t)), a !== null)) {
              ((e.blockedOn = a),
                qr(e.priority, function () {
                  Wc(t);
                }));
              return;
            }
          } else if (
            a === 3 &&
            t.stateNode.current.memoizedState.isDehydrated
          ) {
            e.blockedOn = t.tag === 3 ? t.stateNode.containerInfo : null;
            return;
          }
        }
      }
      e.blockedOn = null;
    }
    function Gd(e) {
      if (e.blockedOn !== null) return !1;
      for (var a = e.targetContainers; 0 < a.length; ) {
        var t = _n(e.nativeEvent);
        if (t === null) {
          t = e.nativeEvent;
          var l = new t.constructor(t.type, t);
          ((dn = l), t.target.dispatchEvent(l), (dn = null));
        } else return ((a = xu(t)), a !== null && Hh(a), (e.blockedOn = t), !1);
        a.shift();
      }
      return !0;
    }
    function em(e, a, t) {
      Gd(e) && t.delete(a);
    }
    function cy() {
      ((Gn = !1),
        zt !== null && Gd(zt) && (zt = null),
        Rt !== null && Gd(Rt) && (Rt = null),
        Ot !== null && Gd(Ot) && (Ot = null),
        So.forEach(em),
        vo.forEach(em));
    }
    function Md(e, a) {
      e.blockedOn === a &&
        ((e.blockedOn = null),
        Gn ||
          ((Gn = !0),
          ye.unstable_scheduleCallback(ye.unstable_NormalPriority, cy)));
    }
    var wd = null;
    function am(e) {
      wd !== e &&
        ((wd = e),
        ye.unstable_scheduleCallback(ye.unstable_NormalPriority, function () {
          wd === e && (wd = null);
          for (var a = 0; a < e.length; a += 3) {
            var t = e[a],
              l = e[a + 1],
              u = e[a + 2];
            if (typeof l != "function") {
              if (Ui(l || t) === null) continue;
              break;
            }
            var o = xu(t);
            o !== null &&
              (e.splice(a, 3),
              (a -= 3),
              In(
                o,
                { pending: !0, data: u, method: t.method, action: l },
                l,
                u,
              ));
          }
        }));
    }
    function Lu(e) {
      function a(n) {
        return Md(n, e);
      }
      (zt !== null && Md(zt, e),
        Rt !== null && Md(Rt, e),
        Ot !== null && Md(Ot, e),
        So.forEach(a),
        vo.forEach(a));
      for (var t = 0; t < yt.length; t++) {
        var l = yt[t];
        l.blockedOn === e && (l.blockedOn = null);
      }
      for (; 0 < yt.length && ((t = yt[0]), t.blockedOn === null); )
        (Ph(t), t.blockedOn === null && yt.shift());
      if (((t = (e.ownerDocument || e).$$reactFormReplay), t != null))
        for (l = 0; l < t.length; l += 3) {
          var u = t[l],
            o = t[l + 1],
            d = u[Ge] || null;
          if (typeof o == "function") d || am(t);
          else if (d) {
            var f = null;
            if (o && o.hasAttribute("formAction")) {
              if (((u = o), (d = o[Ge] || null))) f = d.formAction;
              else if (Ui(u) !== null) continue;
            } else f = d.action;
            (typeof f == "function"
              ? (t[l + 1] = f)
              : (t.splice(l, 3), (l -= 3)),
              am(t));
          }
        }
    }
    function _h() {
      function e(o) {
        o.canIntercept &&
          o.info === "react-transition" &&
          o.intercept({
            handler: function () {
              return new Promise(function (d) {
                return (u = d);
              });
            },
            focusReset: "manual",
            scroll: "manual",
          });
      }
      function a() {
        (u !== null && (u(), (u = null)), l || setTimeout(t, 20));
      }
      function t() {
        if (!l && !navigation.transition) {
          var o = navigation.currentEntry;
          o &&
            o.url != null &&
            navigation.navigate(o.url, {
              state: o.getState(),
              info: "react-transition",
              history: "replace",
            });
        }
      }
      if (typeof navigation == "object") {
        var l = !1,
          u = null;
        return (
          navigation.addEventListener("navigate", e),
          navigation.addEventListener("navigatesuccess", a),
          navigation.addEventListener("navigateerror", a),
          setTimeout(t, 100),
          function () {
            ((l = !0),
              navigation.removeEventListener("navigate", e),
              navigation.removeEventListener("navigatesuccess", a),
              navigation.removeEventListener("navigateerror", a),
              u !== null && (u(), (u = null)));
          }
        );
      }
    }
    function Hi(e) {
      this._internalRoot = e;
    }
    Os.prototype.render = Hi.prototype.render = function (e) {
      var a = this._internalRoot;
      if (a === null) throw Error(v(409));
      var t = a.current,
        l = ea();
      Uh(t, l, e, a, null, null);
    };
    Os.prototype.unmount = Hi.prototype.unmount = function () {
      var e = this._internalRoot;
      if (e !== null) {
        this._internalRoot = null;
        var a = e.containerInfo;
        (Uh(e.current, 2, null, e, null, null), Bs(), (a[gu] = null));
      }
    };
    function Os(e) {
      this._internalRoot = e;
    }
    Os.prototype.unstable_scheduleHydration = function (e) {
      if (e) {
        var a = gm();
        e = { blockedOn: null, target: e, priority: a };
        for (var t = 0; t < yt.length && a !== 0 && a < yt[t].priority; t++);
        (yt.splice(t, 0, e), t === 0 && Ph(e));
      }
    };
    var tm = lm.version;
    if (tm !== "19.2.6") throw Error(v(527, tm, "19.2.6"));
    K.findDOMNode = function (e) {
      var a = e._reactInternals;
      if (a === void 0)
        throw typeof e.render == "function"
          ? Error(v(188))
          : ((e = Object.keys(e).join(",")), Error(v(268, e)));
      return (
        (e = Vg(a)),
        (e = e !== null ? sm(e) : null),
        (e = e === null ? null : e.stateNode),
        e
      );
    };
    var my = {
      bundleType: 0,
      version: "19.2.6",
      rendererPackageName: "react-dom",
      currentDispatcherRef: R,
      reconcilerVersion: "19.2.6",
    };
    if (
      typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ < "u" &&
      ((Gu = __REACT_DEVTOOLS_GLOBAL_HOOK__),
      !Gu.isDisabled && Gu.supportsFiber)
    )
      try {
        ((bo = Gu.inject(my)), (We = Gu));
      } catch {}
    var Gu;
    Es.createRoot = function (e, a) {
      if (!um(e)) throw Error(v(299));
      var t = !1,
        l = "",
        u = Rp,
        o = Op,
        d = Ep;
      return (
        a != null &&
          (a.unstable_strictMode === !0 && (t = !0),
          a.identifierPrefix !== void 0 && (l = a.identifierPrefix),
          a.onUncaughtError !== void 0 && (u = a.onUncaughtError),
          a.onCaughtError !== void 0 && (o = a.onCaughtError),
          a.onRecoverableError !== void 0 && (d = a.onRecoverableError)),
        (a = qh(e, 1, !1, null, null, t, l, null, u, o, d, _h)),
        (e[gu] = a.current),
        Ri(e),
        new Hi(a)
      );
    };
    Es.hydrateRoot = function (e, a, t) {
      if (!um(e)) throw Error(v(299));
      var l = !1,
        u = "",
        o = Rp,
        d = Op,
        f = Ep,
        n = null;
      return (
        t != null &&
          (t.unstable_strictMode === !0 && (l = !0),
          t.identifierPrefix !== void 0 && (u = t.identifierPrefix),
          t.onUncaughtError !== void 0 && (o = t.onUncaughtError),
          t.onCaughtError !== void 0 && (d = t.onCaughtError),
          t.onRecoverableError !== void 0 && (f = t.onRecoverableError),
          t.formState !== void 0 && (n = t.formState)),
        (a = qh(e, 1, !0, a, t ?? null, l, u, n, o, d, f, _h)),
        (a.context = Nh(null)),
        (t = a.current),
        (l = ea()),
        (l = jn(l)),
        (u = wt(l)),
        (u.callback = null),
        Dt(t, u, l),
        (t = l),
        (a.current.lanes = t),
        ko(a, t),
        qa(a),
        (e[gu] = a.current),
        Ri(e),
        new Os(a)
      );
    };
    Es.version = "19.2.6";
  });
  var Yh = Da((Dy, Xh) => {
    "use strict";
    function Vh() {
      if (
        !(
          typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ > "u" ||
          typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE != "function"
        )
      )
        try {
          __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(Vh);
        } catch (e) {
          console.error(e);
        }
    }
    (Vh(), (Xh.exports = Gh()));
  });
  var eg = Da((Us) => {
    "use strict";
    var Ly = Symbol.for("react.transitional.element"),
      gy = Symbol.for("react.fragment");
    function $L(e, a, t) {
      var l = null;
      if (
        (t !== void 0 && (l = "" + t),
        a.key !== void 0 && (l = "" + a.key),
        "key" in a)
      ) {
        t = {};
        for (var u in a) u !== "key" && (t[u] = a[u]);
      } else t = a;
      return (
        (a = t.ref),
        {
          $$typeof: Ly,
          type: e,
          key: l,
          ref: a !== void 0 ? a : null,
          props: t,
        }
      );
    }
    Us.Fragment = gy;
    Us.jsx = $L;
    Us.jsxs = $L;
  });
  var da = Da((Gv, ag) => {
    "use strict";
    ag.exports = eg();
  });
  var ig = ce(Yh());
  var q = ce(ya());
  var qs = ce(ya(), 1);
  var jh = (e) => e?.replace(/([a-z0-9])([A-Z])/g, "$1-$2").toLowerCase();
  function Zh(e, a, t = []) {
    if (a == null)
      throw new Error("[lucide]: iconNode is required when icon name is used");
    return {
      name: jh(e),
      size: 24,
      node: a,
      ...(t.length > 0 ? { aliases: t } : {}),
    };
  }
  var Qh = (e) => {
    let a = "",
      t = !1;
    for (let l of e) {
      if (l === "-" || l === "_" || l <= " ") {
        t = a.length > 0;
        continue;
      }
      (a.length === 0 ? (a += l.toLowerCase()) : (a += t ? l.toUpperCase() : l),
        (t = !1));
    }
    return a;
  };
  var Kh = (e) => {
    let a = Qh(e);
    return a.charAt(0).toUpperCase() + a.slice(1);
  };
  var qo = ce(ya(), 1);
  var Eo = (...e) =>
    e
      .filter((a, t, l) => !!a && a.trim() !== "" && l.indexOf(a) === t)
      .join(" ")
      .trim();
  var Pt = {
    xmlns: "http://www.w3.org/2000/svg",
    width: 24,
    height: 24,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    "stroke-width": 2,
    "stroke-linecap": "round",
    "stroke-linejoin": "round",
  };
  function Fi(e) {
    return e != null;
  }
  function Jh(e, a = {}) {
    let t = a.attributeNames ?? {},
      l = (m) => t[m] ?? m,
      u = e.size ?? e.width ?? Pt.width,
      o = e.size ?? e.height ?? Pt.height,
      d =
        e.aliases
          ?.filter((m) => typeof m == "string" && m.trim() !== "")
          .map((m) => `lucide-${m}`) ?? [],
      f = [...(e.name ? [`lucide-${e.name}`] : []), ...d],
      n = a.className?.split(" ").filter(Boolean) ?? [],
      p = a.includeDefaultClasses === !1 ? Eo(...n) : Eo("lucide", ...f, ...n),
      h = a.absoluteStrokeWidth
        ? (Number(a.strokeWidth ?? Pt["stroke-width"]) *
            Number(e.size ?? e.width ?? Pt.width)) /
          Number(a.size ?? a.width ?? Pt.width)
        : (a.strokeWidth ?? Pt["stroke-width"]);
    return [
      "svg",
      {
        ...Object.entries(Pt).reduce((m, [L, k]) => ((m[l(L)] = k), m), {}),
        ...("color" in a && a.color && { [l("stroke")]: a.color }),
        ...("size" in a &&
          Fi(a.size) && { [l("width")]: a.size, [l("height")]: a.size }),
        ...("width" in a && Fi(a.width) && { [l("width")]: a.width }),
        ...("height" in a && Fi(a.height) && { [l("height")]: a.height }),
        [l("stroke-width")]: h,
        ...(p && { [l("class")]: p }),
        [l("viewBox")]: `0 0 ${u} ${o}`,
        ...(a.hasA11yProp === !1 ? { [l("aria-hidden")]: "true" } : {}),
        ...("attributes" in a && a.attributes),
      },
      e.node.map((m) => {
        let [L, k, w] = m,
          A = a.nonScalingStroke
            ? { [l("vector-effect")]: "non-scaling-stroke", ...k }
            : k;
        return w ? [L, A, w] : [L, A];
      }),
    ];
  }
  function Wh(e, a = {}) {
    return Jh(e, {
      ...a,
      attributeNames: {
        ...a.attributeNames,
        class: "className",
        "stroke-width": "strokeWidth",
        "stroke-linecap": "strokeLinecap",
        "stroke-linejoin": "strokeLinejoin",
        "vector-effect": "vectorEffect",
      },
    });
  }
  var $h = (e) => {
    for (let a in e)
      if (a.startsWith("aria-") || a === "role" || a === "title") return !0;
    return !1;
  };
  var bu = ce(ya(), 1);
  var py = (0, bu.createContext)({});
  var eL = () => (0, bu.useContext)(py);
  var aL = (0, qo.forwardRef)(
    (
      {
        color: e,
        size: a,
        width: t,
        height: l,
        strokeWidth: u,
        absoluteStrokeWidth: o,
        nonScalingStroke: d,
        className: f = "",
        children: n,
        iconNode: p = [],
        icon: h = { node: p, aliases: [], size: 24 },
        ...x
      },
      m,
    ) => {
      let {
          size: L = 24,
          strokeWidth: k = 2,
          absoluteStrokeWidth: w = !1,
          nonScalingStroke: A = !1,
          color: c = "currentColor",
          className: r = "",
        } = eL() ?? {},
        g = !!n || $h(x),
        [y, T, V = []] = Wh(h, {
          color: e ?? c,
          width: t ?? a ?? L,
          height: l ?? a ?? L,
          strokeWidth: u ?? k,
          absoluteStrokeWidth: o ?? w,
          nonScalingStroke: d ?? A,
          className: Eo(r, f),
          hasA11yProp: g,
          attributes: x,
        });
      return (0, qo.createElement)(y, { ref: m, ...T }, [
        ...V.map(([D, B]) => (0, qo.createElement)(D, B)),
        ...(Array.isArray(n) ? n : [n]),
      ]);
    },
  );
  function I(e, a = [], t = []) {
    let l = typeof e == "string" ? Zh(e, a, t) : e,
      u = (0, qs.forwardRef)(({ className: o, ...d }, f) =>
        (0, qs.createElement)(aL, { ref: f, icon: l, className: o, ...d }),
      );
    return (l.name && (u.displayName = Kh(l.name)), u);
  }
  var tL = {
    name: "arrow-left",
    size: 24,
    node: [
      ["path", { d: "m12 19-7-7 7-7", key: "1l729n" }],
      ["path", { d: "M19 12H5", key: "x3x0zl" }],
    ],
  };
  tL.node;
  var No = I(tL);
  var lL = {
    name: "arrow-right",
    size: 24,
    node: [
      ["path", { d: "M5 12h14", key: "1ays0h" }],
      ["path", { d: "m12 5 7 7-7 7", key: "xquz4c" }],
    ],
  };
  lL.node;
  var Ca = I(lL);
  var uL = {
    name: "arrow-up-right",
    size: 24,
    node: [
      ["path", { d: "M7 7h10v10", key: "1tivn9" }],
      ["path", { d: "M7 17 17 7", key: "1vkiza" }],
    ],
  };
  uL.node;
  var Na = I(uL);
  var oL = {
    name: "bell",
    size: 24,
    node: [
      ["path", { d: "M10.268 21a2 2 0 0 0 3.464 0", key: "vwvbt9" }],
      [
        "path",
        {
          d: "M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326",
          key: "11g9vi",
        },
      ],
    ],
  };
  oL.node;
  var Uo = I(oL);
  var dL = {
    name: "book-heart",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20",
          key: "k3hazp",
        },
      ],
      [
        "path",
        {
          d: "M8.62 9.8A2.25 2.25 0 1 1 12 6.836a2.25 2.25 0 1 1 3.38 2.966l-2.626 2.856a.998.998 0 0 1-1.507 0z",
          key: "9v40y5",
        },
      ],
    ],
  };
  dL.node;
  var _t = I(dL);
  var sL = {
    name: "calendar-days",
    size: 24,
    node: [
      ["path", { d: "M8 2v3", key: "1ioesn" }],
      ["path", { d: "M16 2v3", key: "otl347" }],
      [
        "rect",
        { x: "3", y: "3", width: "18", height: "18", rx: "2", key: "h1oib" },
      ],
      ["path", { d: "M3 9h18", key: "1pudct" }],
      ["path", { d: "M8 13h.01", key: "1sbv64" }],
      ["path", { d: "M12 13h.01", key: "y0uutt" }],
      ["path", { d: "M16 13h.01", key: "wip0gl" }],
      ["path", { d: "M8 17h.01", key: "p3bg7i" }],
      ["path", { d: "M12 17h.01", key: "p32p05" }],
      ["path", { d: "M16 17h.01", key: "ql8jdd" }],
    ],
  };
  sL.node;
  var nt = I(sL);
  var fL = {
    name: "check",
    size: 24,
    node: [["path", { d: "M20 6 9 17l-5-5", key: "1gmf2c" }]],
  };
  fL.node;
  var la = I(fL);
  var nL = {
    name: "chevron-down",
    size: 24,
    node: [["path", { d: "m6 9 6 6 6-6", key: "qrunsl" }]],
  };
  nL.node;
  var Au = I(nL);
  var iL = {
    name: "chevron-left",
    size: 24,
    node: [["path", { d: "m15 18-6-6 6-6", key: "1wnfg3" }]],
  };
  iL.node;
  var Ho = I(iL);
  var rL = {
    name: "chevron-right",
    size: 24,
    node: [["path", { d: "m9 18 6-6-6-6", key: "mthhwq" }]],
  };
  rL.node;
  var ku = I(rL);
  var cL = {
    name: "circle-question-mark",
    size: 24,
    node: [
      ["circle", { cx: "12", cy: "12", r: "10", key: "1mglay" }],
      ["path", { d: "M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3", key: "1u773s" }],
      ["path", { d: "M12 17h.01", key: "p32p05" }],
    ],
    aliases: ["help-circle", "circle-help"],
  };
  cL.node;
  var ba = I(cL);
  var mL = {
    name: "copy",
    size: 24,
    node: [
      [
        "rect",
        {
          width: "14",
          height: "14",
          x: "8",
          y: "8",
          rx: "2",
          ry: "2",
          key: "17jyea",
        },
      ],
      [
        "path",
        {
          d: "M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2",
          key: "zix9uf",
        },
      ],
    ],
  };
  mL.node;
  var Fo = I(mL);
  var pL = {
    name: "database",
    size: 24,
    node: [
      ["ellipse", { cx: "12", cy: "5", rx: "9", ry: "3", key: "msslwz" }],
      ["path", { d: "M3 5V19A9 3 0 0 0 21 19V5", key: "1wlel7" }],
      ["path", { d: "M3 12A9 3 0 0 0 21 12", key: "mv7ke4" }],
    ],
  };
  pL.node;
  var Gt = I(pL);
  var hL = {
    name: "download",
    size: 24,
    node: [
      ["path", { d: "M12 15V3", key: "m9g1x1" }],
      [
        "path",
        { d: "M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4", key: "ih7n3h" },
      ],
      ["path", { d: "m7 10 5 5 5-5", key: "brsn70" }],
    ],
  };
  hL.node;
  var Vt = I(hL);
  var LL = {
    name: "ellipsis",
    size: 24,
    node: [
      ["circle", { cx: "12", cy: "12", r: "1", key: "41hilf" }],
      ["circle", { cx: "19", cy: "12", r: "1", key: "1wjl8i" }],
      ["circle", { cx: "5", cy: "12", r: "1", key: "1pcz8c" }],
    ],
    aliases: ["more-horizontal"],
  };
  LL.node;
  var Xt = I(LL);
  var gL = {
    name: "eye-off",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49",
          key: "ct8e1f",
        },
      ],
      ["path", { d: "M14.084 14.158a3 3 0 0 1-4.242-4.242", key: "151rxh" }],
      [
        "path",
        {
          d: "M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143",
          key: "13bj9a",
        },
      ],
      ["path", { d: "m2 2 20 20", key: "1ooewy" }],
    ],
  };
  gL.node;
  var Ua = I(gL);
  var xL = {
    name: "eye",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0",
          key: "1nclc0",
        },
      ],
      ["circle", { cx: "12", cy: "12", r: "3", key: "1v7zrd" }],
    ],
  };
  xL.node;
  var Ha = I(xL);
  var CL = {
    name: "folder-heart",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M10.638 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v3.417",
          key: "10r6g4",
        },
      ],
      [
        "path",
        {
          d: "M14.62 18.8A2.25 2.25 0 1 1 18 15.836a2.25 2.25 0 1 1 3.38 2.966l-2.626 2.856a.998.998 0 0 1-1.507 0z",
          key: "15cy7q",
        },
      ],
    ],
  };
  CL.node;
  var Po = I(CL);
  var yL = {
    name: "folder-lock",
    size: 24,
    node: [
      [
        "rect",
        { width: "8", height: "5", x: "14", y: "17", rx: "1", key: "19aais" },
      ],
      [
        "path",
        {
          d: "M10 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v2.5",
          key: "1w6v7t",
        },
      ],
      ["path", { d: "M20 17v-2a2 2 0 1 0-4 0v2", key: "pwaxnr" }],
    ],
  };
  yL.node;
  var Yt = I(yL);
  var SL = {
    name: "gift",
    size: 24,
    node: [
      ["path", { d: "M12 7v14", key: "1akyts" }],
      [
        "path",
        { d: "M20 11v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8", key: "1sqzm4" },
      ],
      [
        "path",
        {
          d: "M7.5 7a1 1 0 0 1 0-5A4.8 8 0 0 1 12 7a4.8 8 0 0 1 4.5-5 1 1 0 0 1 0 5",
          key: "kc0143",
        },
      ],
      [
        "rect",
        { x: "3", y: "7", width: "18", height: "4", rx: "1", key: "1hberx" },
      ],
    ],
  };
  SL.node;
  var Mu = I(SL);
  var vL = {
    name: "globe",
    size: 24,
    node: [
      ["circle", { cx: "12", cy: "12", r: "10", key: "1mglay" }],
      [
        "path",
        { d: "M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20", key: "13o1zl" },
      ],
      ["path", { d: "M2 12h20", key: "9i4pu4" }],
    ],
  };
  vL.node;
  var bl = I(vL);
  var IL = {
    name: "heart",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5",
          key: "mvr1a0",
        },
      ],
    ],
  };
  IL.node;
  var ua = I(IL);
  var bL = {
    name: "house",
    size: 24,
    node: [
      [
        "path",
        { d: "M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8", key: "5wwlr5" },
      ],
      [
        "path",
        {
          d: "M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z",
          key: "r6nss1",
        },
      ],
    ],
    aliases: ["home"],
  };
  bL.node;
  var jt = I(bL);
  var AL = {
    name: "landmark",
    size: 24,
    node: [
      ["path", { d: "M10 18v-7", key: "wt116b" }],
      [
        "path",
        {
          d: "M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z",
          key: "yxxwt6",
        },
      ],
      ["path", { d: "M14 18v-7", key: "vav6t3" }],
      ["path", { d: "M18 18v-7", key: "aexdmj" }],
      ["path", { d: "M3 22h18", key: "8prr45" }],
      ["path", { d: "M6 18v-7", key: "1ivflk" }],
    ],
  };
  AL.node;
  var Al = I(AL);
  var kL = {
    name: "layout-dashboard",
    size: 24,
    node: [
      [
        "rect",
        { width: "7", height: "9", x: "3", y: "3", rx: "1", key: "10lvy0" },
      ],
      [
        "rect",
        { width: "7", height: "5", x: "14", y: "3", rx: "1", key: "16une8" },
      ],
      [
        "rect",
        { width: "7", height: "9", x: "14", y: "12", rx: "1", key: "1hutg5" },
      ],
      [
        "rect",
        { width: "7", height: "5", x: "3", y: "16", rx: "1", key: "ldoo1y" },
      ],
    ],
  };
  kL.node;
  var _o = I(kL);
  var ML = {
    name: "layout-grid",
    size: 24,
    node: [
      [
        "rect",
        { width: "7", height: "7", x: "3", y: "3", rx: "1", key: "1g98yp" },
      ],
      [
        "rect",
        { width: "7", height: "7", x: "14", y: "3", rx: "1", key: "6d4xhi" },
      ],
      [
        "rect",
        { width: "7", height: "7", x: "14", y: "14", rx: "1", key: "nxv5o0" },
      ],
      [
        "rect",
        { width: "7", height: "7", x: "3", y: "14", rx: "1", key: "1bb6yr" },
      ],
    ],
  };
  ML.node;
  var Go = I(ML);
  var wL = {
    name: "leaf",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M11 20a10 10 0 0010-10 25.9 25.9 0 00-1.04-7.281 1 1 0 00-1.755-.325C15.833 5.5 13 5.5 9.8 6.1A7 7 0 0011 20",
          key: "1wjnjv",
        },
      ],
      [
        "path",
        {
          d: "M2 21a5 5 0 012.911-4.544C7.613 15.212 8.351 15.24 11 13",
          key: "c1ejpn",
        },
      ],
    ],
  };
  wL.node;
  var kl = I(wL);
  var DL = {
    name: "list",
    size: 24,
    node: [
      ["path", { d: "M3 5h.01", key: "18ugdj" }],
      ["path", { d: "M3 12h.01", key: "nlz23k" }],
      ["path", { d: "M3 19h.01", key: "noohij" }],
      ["path", { d: "M8 5h13", key: "1pao27" }],
      ["path", { d: "M8 12h13", key: "1za7za" }],
      ["path", { d: "M8 19h13", key: "m83p4d" }],
    ],
  };
  DL.node;
  var Vo = I(DL);
  var TL = {
    name: "loader-circle",
    size: 24,
    node: [["path", { d: "M21 12a9 9 0 1 1-6.219-8.56", key: "13zald" }]],
    aliases: ["loader-2"],
  };
  TL.node;
  var Re = I(TL);
  var BL = {
    name: "lock-keyhole",
    size: 24,
    node: [
      ["circle", { cx: "12", cy: "16", r: "1", key: "1au0dj" }],
      [
        "rect",
        { x: "3", y: "10", width: "18", height: "12", rx: "2", key: "6s8ecr" },
      ],
      ["path", { d: "M7 10V7a5 5 0 0 1 10 0v3", key: "1pqi11" }],
    ],
  };
  BL.node;
  var ne = I(BL);
  var zL = {
    name: "mail",
    size: 24,
    node: [
      ["path", { d: "m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7", key: "132q7q" }],
      [
        "rect",
        { x: "2", y: "4", width: "20", height: "16", rx: "2", key: "izxlao" },
      ],
    ],
  };
  zL.node;
  var wu = I(zL);
  var RL = {
    name: "map-pin",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0",
          key: "1r0f0z",
        },
      ],
      ["circle", { cx: "12", cy: "10", r: "3", key: "ilqhr7" }],
    ],
  };
  RL.node;
  var Xo = I(RL);
  var OL = {
    name: "menu",
    size: 24,
    node: [
      ["path", { d: "M4 5h16", key: "1tepv9" }],
      ["path", { d: "M4 12h16", key: "1lakjw" }],
      ["path", { d: "M4 19h16", key: "1djgab" }],
    ],
  };
  OL.node;
  var Yo = I(OL);
  var EL = {
    name: "moon",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401",
          key: "kfwtm",
        },
      ],
    ],
  };
  EL.node;
  var jo = I(EL);
  var qL = {
    name: "notebook-pen",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4",
          key: "re6nr2",
        },
      ],
      ["path", { d: "M2 6h4", key: "aawbzj" }],
      ["path", { d: "M2 10h4", key: "l0bgd4" }],
      ["path", { d: "M2 14h4", key: "1gsvsf" }],
      ["path", { d: "M2 18h4", key: "1bu2t1" }],
      [
        "path",
        {
          d: "M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z",
          key: "pqwjuv",
        },
      ],
    ],
  };
  qL.node;
  var Zt = I(qL);
  var NL = {
    name: "pencil",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z",
          key: "1a8usu",
        },
      ],
      ["path", { d: "m15 5 4 4", key: "1mk7zo" }],
    ],
  };
  NL.node;
  var Qt = I(NL);
  var UL = {
    name: "phone",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384",
          key: "9njp5v",
        },
      ],
    ],
  };
  UL.node;
  var Du = I(UL);
  var HL = {
    name: "plus",
    size: 24,
    node: [
      ["path", { d: "M5 12h14", key: "1ays0h" }],
      ["path", { d: "M12 5v14", key: "s699le" }],
    ],
  };
  HL.node;
  var Aa = I(HL);
  var FL = {
    name: "search",
    size: 24,
    node: [
      ["path", { d: "m21 21-4.34-4.34", key: "14j7rj" }],
      ["circle", { cx: "11", cy: "11", r: "8", key: "4ej97u" }],
    ],
  };
  FL.node;
  var Ml = I(FL);
  var PL = {
    name: "server",
    size: 24,
    node: [
      [
        "rect",
        {
          width: "20",
          height: "8",
          x: "2",
          y: "2",
          rx: "2",
          ry: "2",
          key: "ngkwjq",
        },
      ],
      [
        "rect",
        {
          width: "20",
          height: "8",
          x: "2",
          y: "14",
          rx: "2",
          ry: "2",
          key: "iecqi9",
        },
      ],
      ["line", { x1: "6", x2: "6.01", y1: "6", y2: "6", key: "16zg32" }],
      ["line", { x1: "6", x2: "6.01", y1: "18", y2: "18", key: "nzw8ys" }],
    ],
  };
  PL.node;
  var Zo = I(PL);
  var _L = {
    name: "settings-2",
    size: 24,
    node: [
      ["path", { d: "M14 17H5", key: "gfn3mx" }],
      ["path", { d: "M19 7h-9", key: "6i9tg" }],
      ["circle", { cx: "17", cy: "17", r: "3", key: "18b49y" }],
      ["circle", { cx: "7", cy: "7", r: "3", key: "dfmy0x" }],
    ],
  };
  _L.node;
  var wl = I(_L);
  var GL = {
    name: "shield-check",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z",
          key: "oel41y",
        },
      ],
      ["path", { d: "m9 12 2 2 4-4", key: "dzmm74" }],
    ],
  };
  GL.node;
  var Xe = I(GL);
  var VL = {
    name: "sliders-horizontal",
    size: 24,
    node: [
      ["path", { d: "M10 5H3", key: "1qgfaw" }],
      ["path", { d: "M12 19H3", key: "yhmn1j" }],
      ["path", { d: "M14 3v4", key: "1sua03" }],
      ["path", { d: "M16 17v4", key: "1q0r14" }],
      ["path", { d: "M21 12h-9", key: "1o4lsq" }],
      ["path", { d: "M21 19h-5", key: "1rlt1p" }],
      ["path", { d: "M21 5h-7", key: "1oszz2" }],
      ["path", { d: "M8 10v4", key: "tgpxqk" }],
      ["path", { d: "M8 12H3", key: "a7s4jb" }],
    ],
  };
  VL.node;
  var Qo = I(VL);
  var XL = {
    name: "sparkles",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z",
          key: "1s2grr",
        },
      ],
      ["path", { d: "M20 2v4", key: "1rf3ol" }],
      ["path", { d: "M22 4h-4", key: "gwowj6" }],
      ["circle", { cx: "4", cy: "20", r: "2", key: "6kqj1y" }],
    ],
    aliases: ["stars"],
  };
  XL.node;
  var Kt = I(XL);
  var YL = {
    name: "star",
    size: 24,
    node: [
      [
        "path",
        {
          d: "M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z",
          key: "r04s7s",
        },
      ],
    ],
  };
  YL.node;
  var Jt = I(YL);
  var jL = {
    name: "sun",
    size: 24,
    node: [
      ["circle", { cx: "12", cy: "12", r: "4", key: "4exip2" }],
      ["path", { d: "M12 2v2", key: "tus03m" }],
      ["path", { d: "M12 20v2", key: "1lh1kg" }],
      ["path", { d: "m4.93 4.93 1.41 1.41", key: "149t6j" }],
      ["path", { d: "m17.66 17.66 1.41 1.41", key: "ptbguv" }],
      ["path", { d: "M2 12h2", key: "1t8f8n" }],
      ["path", { d: "M20 12h2", key: "1q8mjw" }],
      ["path", { d: "m6.34 17.66-1.41 1.41", key: "1m8zz5" }],
      ["path", { d: "m19.07 4.93-1.41 1.41", key: "1shlcs" }],
    ],
  };
  jL.node;
  var Tu = I(jL);
  var ZL = {
    name: "trash",
    size: 24,
    node: [
      ["path", { d: "M10 11v6", key: "nco0om" }],
      ["path", { d: "M14 11v6", key: "outv1u" }],
      [
        "path",
        { d: "M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6", key: "miytrc" },
      ],
      ["path", { d: "M3 6h18", key: "d0wm0j" }],
      ["path", { d: "M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2", key: "e791ji" }],
    ],
    aliases: ["trash-2"],
  };
  ZL.node;
  var oa = I(ZL);
  var QL = {
    name: "users",
    size: 24,
    node: [
      [
        "path",
        { d: "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2", key: "1yyitq" },
      ],
      ["path", { d: "M16 3.128a4 4 0 0 1 0 7.744", key: "16gr8j" }],
      ["path", { d: "M22 21v-2a4 4 0 0 0-3-3.87", key: "kshegd" }],
      ["circle", { cx: "9", cy: "7", r: "4", key: "nufk8" }],
    ],
  };
  QL.node;
  var Wt = I(QL);
  var KL = {
    name: "x",
    size: 24,
    node: [
      ["path", { d: "M18 6 6 18", key: "1bl5f8" }],
      ["path", { d: "m6 6 12 12", key: "d8bk6v" }],
    ],
  };
  KL.node;
  var Fa = I(KL);
  var JL = [
    "Mother",
    "Father",
    "Brother",
    "Sister",
    "Son",
    "Daughter",
    "Friend",
    "Best-Friend",
    "Family",
    "Work",
    "Girl-Friend",
    "Boy-Friend",
    "Partner",
    "Ex",
    "Fling",
    "Friends with Benefits",
    "One Night Stand",
    "Other",
  ];
  function ka(e, a = new Date()) {
    if (!e) return null;
    let t = new Date(`${e}T12:00:00`);
    return Number.isNaN(t.getTime())
      ? null
      : a.getFullYear() -
          t.getFullYear() -
          (a.getMonth() < t.getMonth() ||
          (a.getMonth() === t.getMonth() && a.getDate() < t.getDate())
            ? 1
            : 0);
  }
  function Ne(e) {
    if (!e) return 999;
    let a = new Date();
    a.setHours(0, 0, 0, 0);
    let t = new Date(`${e}T12:00:00`),
      l = new Date(a.getFullYear(), t.getMonth(), t.getDate());
    return (
      l < a && (l = new Date(a.getFullYear() + 1, t.getMonth(), t.getDate())),
      Math.round((l.getTime() - a.getTime()) / 864e5)
    );
  }
  function $t(e) {
    return e
      ? new Date(`${e}T12:00:00`).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
        })
      : "Not added";
  }
  function Ko(e) {
    return e
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map((a) => a[0])
      .join("")
      .toUpperCase();
  }
  function Ns() {
    let e = new Date();
    return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}-${String(e.getDate()).padStart(2, "0")}`;
  }
  function Pi(e, a) {
    let t = new Date();
    return (
      t.setDate(t.getDate() + a),
      `${e}-${String(t.getMonth() + 1).padStart(2, "0")}-${String(t.getDate()).padStart(2, "0")}`
    );
  }
  var hy = [
      {
        id: "sarah",
        name: "Sarah Mitchell",
        title: "The friend who feels like family",
        group: "Friends",
        dob: Pi(1994, 3),
        email: "sarah.m@email.com",
        phone: "+1 (415) 555-0123",
        address: "124 Valencia Street, San Francisco, CA",
        notes:
          "Loves a good book, matcha, and spontaneous weekend trips. Met at the little bookstore on Valencia.",
        avatar: "sarah",
        favorite: !0,
      },
      {
        id: "james",
        name: "James Wilson",
        title: "Product designer \xB7 Studio North",
        group: "Work",
        dob: "1990-10-25",
        email: "james.w@studionorth.co",
        phone: "+1 (628) 555-0146",
        address: "",
        notes: "Our go-to person for thoughtful design feedback.",
        avatar: "james",
        favorite: !1,
      },
      {
        id: "emma",
        name: "Emma Thompson",
        title: "Sister & forever adventure buddy",
        group: "Family",
        dob: Pi(1997, 8),
        email: "emma.t@email.com",
        phone: "+1 (415) 555-0198",
        address: "82 Oak Avenue, Portland, OR",
        notes:
          "Coffee order: oat milk flat white. Usually free for a call on Sundays.",
        avatar: "emma",
        favorite: !0,
      },
      {
        id: "michael",
        name: "Michael Chen",
        title: "Good coffee, better conversations",
        group: "Friends",
        dob: "1992-02-16",
        email: "michael.chen@email.com",
        phone: "+1 (650) 555-0167",
        address: "",
        notes: "Met through the Saturday running club.",
        avatar: "michael",
        favorite: !1,
      },
      {
        id: "olivia",
        name: "Olivia Parker",
        title: "Marketing lead \xB7 Bloom",
        group: "Work",
        dob: "1995-08-09",
        email: "olivia@bloom.studio",
        phone: "+1 (510) 555-0134",
        address: "",
        notes: "Prefers email for work updates.",
        avatar: "olivia",
        favorite: !1,
      },
      {
        id: "daniel",
        name: "Daniel Morgan",
        title: "Brother \xB7 unofficial family chef",
        group: "Family",
        dob: Pi(1989, 16),
        email: "daniel.m@email.com",
        phone: "+1 (415) 555-0182",
        address: "43 Maple Lane, Oakland, CA",
        notes: "Ask him for the sourdough recipe. Planning a summer visit.",
        avatar: "daniel",
        favorite: !0,
      },
      {
        id: "sophia",
        name: "Sophia Rodriguez",
        title: "Creative soul & weekend explorer",
        group: "Friends",
        dob: "1996-12-05",
        email: "sophia.r@email.com",
        phone: "+1 (628) 555-0119",
        address: "",
        notes: "Always has the best hiking recommendations.",
        avatar: "sophia",
        favorite: !1,
      },
      {
        id: "alex",
        name: "Alex Turner",
        title: "Engineering lead \xB7 Orbit",
        group: "Work",
        dob: "1991-03-22",
        email: "alex@orbit.dev",
        phone: "+1 (650) 555-0175",
        address: "",
        notes: "Collaborating on the new website project.",
        avatar: "alex",
        favorite: !1,
      },
    ],
    WL = {
      installed: !1,
      authenticated: !0,
      ownerName: "Alex Morgan",
      ownerEmail: "",
      name: "My personal space",
      journalEnabled: !0,
      autoLockMinutes: 15,
      engine: "MySQL",
      mysqlBackup: !1,
      contacts: hy,
      accounts: [],
      journals: [],
    };
  var Hs = ce(ya());
  var F = ce(da());
  function xy(e) {
    return typeof window < "u" && window.__PHP__
      ? `${window.__INSTALL__ ? "../" : ""}assets/${e}`
      : `/${e}`;
  }
  function _i() {
    return typeof window < "u" && window.__PHP__
      ? `${window.__INSTALL__ ? "../" : ""}api.php`
      : "/api/workspace";
  }
  async function De(e, a = {}) {
    let t = await fetch(_i(), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "x-innercircle-request": "1",
        },
        body: JSON.stringify({ action: e, ...a }),
      }),
      l = await t.json();
    if (!t.ok)
      throw (
        t.status === 401 && window.dispatchEvent(new Event("workspace-locked")),
        new Error(l.error || "Something went wrong. Please try again.")
      );
    return l;
  }
  function Jo({ small: e = !1 }) {
    return (0, F.jsxs)("span", {
      className: `brand ${e ? "small" : ""}`,
      children: [
        (0, F.jsx)("span", {
          className: "brand-mark",
          children: (0, F.jsx)("svg", {
            viewBox: "0 0 32 32",
            fill: "none",
            "aria-hidden": "true",
            children: (0, F.jsx)("path", {
              d: "M15 15C7 16 3 12 5 7c5-2 10 0 10 8Zm2 0c-1-8 3-12 8-10 2 5 0 10-8 10Zm0 2c8-1 12 3 10 8-5 2-10 0-10-8Zm-2 0c1 8-3 12-8 10-2-5 0-10 8-10Z",
              fill: "currentColor",
            }),
          }),
        }),
        !e &&
          (0, F.jsxs)("span", {
            children: [
              "innercircle",
              (0, F.jsx)("span", { className: "brand-period", children: "." }),
            ],
          }),
      ],
    });
  }
  function it({ person: e, size: a = "md" }) {
    return (0, F.jsx)("span", {
      className: `avatar avatar-${a} tone-${e.name.charCodeAt(0) % 5}`,
      children: e.avatar
        ? (0, F.jsx)("img", {
            src: xy(`avatars/${e.avatar}.jpg`),
            alt: e.name,
            loading: "lazy",
            onError: (t) => {
              t.currentTarget.style.display = "none";
            },
          })
        : Ko(e.name),
    });
  }
  function Fs({ group: e }) {
    return (0, F.jsxs)("span", {
      className: `badge badge-${e.toLowerCase().replaceAll(" ", "-")}`,
      children: [(0, F.jsx)("i", {}), e],
    });
  }
  function Ma({
    title: e,
    subtitle: a,
    children: t,
    onClose: l,
    wide: u = !1,
  }) {
    let o = (0, Hs.useRef)(null);
    return (
      (0, Hs.useEffect)(() => {
        let d = document.activeElement,
          f = document.body.style.overflow;
        ((document.body.style.overflow = "hidden"),
          o.current?.querySelector("input, select, textarea, button")?.focus());
        function p(h) {
          if (
            (h.key === "Escape" && (h.stopPropagation(), l()), h.key === "Tab")
          ) {
            let x = o.current?.querySelectorAll(
              "button:not([disabled]), input:not([disabled]), textarea, select, a[href]",
            );
            if (!x?.length) return;
            let m = x[0],
              L = x[x.length - 1];
            (h.shiftKey &&
              document.activeElement === m &&
              (h.preventDefault(), L.focus()),
              !h.shiftKey &&
                document.activeElement === L &&
                (h.preventDefault(), m.focus()));
          }
        }
        return (
          document.addEventListener("keydown", p),
          () => {
            ((document.body.style.overflow = f),
              document.removeEventListener("keydown", p),
              d?.focus());
          }
        );
      }, [l]),
      (0, F.jsx)("div", {
        className: "modal-backdrop",
        onMouseDown: (d) => {
          d.target === d.currentTarget && l();
        },
        children: (0, F.jsxs)("div", {
          ref: o,
          role: "dialog",
          "aria-modal": "true",
          "aria-label": e,
          className: `modal ${u ? "modal-wide" : ""}`,
          children: [
            (0, F.jsxs)("header", {
              className: "modal-header",
              children: [
                (0, F.jsxs)("div", {
                  children: [
                    (0, F.jsx)("p", {
                      className: "eyebrow",
                      children: "A LITTLE MORE PERSONAL",
                    }),
                    (0, F.jsx)("h2", { children: e }),
                    a && (0, F.jsx)("p", { className: "muted", children: a }),
                  ],
                }),
                (0, F.jsx)("button", {
                  className: "icon-button close-button",
                  "aria-label": "Close dialog",
                  onClick: l,
                  children: (0, F.jsx)(Fa, { size: 19 }),
                }),
              ],
            }),
            t,
          ],
        }),
      })
    );
  }
  function E({ label: e, hint: a, children: t, full: l = !1 }) {
    return (0, F.jsxs)("label", {
      className: `field ${l ? "field-full" : ""}`,
      children: [
        (0, F.jsx)("span", { children: e }),
        t,
        a && (0, F.jsx)("small", { children: a }),
      ],
    });
  }
  function Bu({ busy: e, children: a, disabled: t = !1 }) {
    return (0, F.jsxs)("button", {
      className: "button primary",
      disabled: e || t,
      type: "submit",
      children: [
        e
          ? (0, F.jsx)(Re, { size: 16, className: "spin" })
          : (0, F.jsx)(la, { size: 16 }),
        " ",
        e ? "Saving\u2026" : a,
      ],
    });
  }
  function rt({ error: e }) {
    return e
      ? (0, F.jsx)("div", {
          className: "form-error",
          role: "alert",
          children: e,
        })
      : null;
  }
  function Dl({ children: e }) {
    return (0, F.jsxs)("div", {
      className: "privacy-note",
      children: [
        (0, F.jsx)(ne, { size: 14 }),
        (0, F.jsx)("span", {
          children: e || "Personal details stay in your private workspace.",
        }),
      ],
    });
  }
  function Wo({ icon: e, title: a, description: t, action: l }) {
    return (0, F.jsxs)("div", {
      className: "empty-state",
      children: [
        (0, F.jsxs)("div", {
          className: "empty-orbit",
          children: [
            (0, F.jsx)("span", { children: e }),
            (0, F.jsx)("i", {}),
            (0, F.jsx)("i", {}),
            (0, F.jsx)("i", {}),
          ],
        }),
        (0, F.jsx)("h2", { children: a }),
        (0, F.jsx)("p", { children: t }),
        l,
      ],
    });
  }
  function Ps(e, a, t = "application/json") {
    let l = URL.createObjectURL(new Blob([a], { type: t })),
      u = document.createElement("a");
    ((u.href = l),
      (u.download = e),
      u.click(),
      setTimeout(() => URL.revokeObjectURL(l), 1e3));
  }
  var Ye = ce(ya());
  var C = ce(da());
  function tg({ contact: e, onDone: a, onClose: t }) {
    let [l, u] = (0, Ye.useState)(e?.dob || ""),
      [o, d] = (0, Ye.useState)(!1),
      [f, n] = (0, Ye.useState)("");
    async function p(h) {
      (h.preventDefault(), d(!0), n(""));
      let x = Object.fromEntries(new FormData(h.currentTarget));
      try {
        (await De("saveContact", {
          ...x,
          id: e?.id,
          favorite: x.favorite === "on",
        }),
          await a(),
          t());
      } catch (m) {
        n(m.message);
      } finally {
        d(!1);
      }
    }
    return (0, C.jsxs)("form", {
      onSubmit: p,
      className: "editor-form",
      children: [
        (0, C.jsxs)("div", {
          className: "form-person",
          children: [
            (0, C.jsx)(it, {
              person: e || { name: "New contact", avatar: "" },
              size: "lg",
            }),
            (0, C.jsxs)("div", {
              children: [
                (0, C.jsx)("strong", {
                  children: e
                    ? "A familiar face"
                    : "Every connection starts somewhere.",
                }),
                (0, C.jsx)("p", {
                  children: e
                    ? "Keep the little details up to date."
                    : "Add someone to your inner circle.",
                }),
              ],
            }),
          ],
        }),
        (0, C.jsxs)("div", {
          className: "form-grid",
          children: [
            (0, C.jsx)(E, {
              label: "Full name *",
              children: (0, C.jsx)("input", {
                name: "name",
                required: !0,
                maxLength: 100,
                defaultValue: e?.name,
                placeholder: "e.g. Sarah Mitchell",
                autoComplete: "name",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Relationship",
              children: (0, C.jsx)("select", {
                name: "group",
                defaultValue: e?.group || "Friends",
                children: JL.map((h) =>
                  (0, C.jsx)("option", { children: h }, h),
                ),
              }),
            }),
            (0, C.jsx)(E, {
              label: "A little about them",
              full: !0,
              children: (0, C.jsx)("input", {
                name: "title",
                maxLength: 120,
                defaultValue: e?.title,
                placeholder: "A role, a relationship, or what makes them, them",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Date of birth",
              children: (0, C.jsx)("input", {
                name: "dob",
                type: "date",
                value: l,
                onChange: (h) => u(h.target.value),
                max: Ns(),
              }),
            }),
            (0, C.jsxs)("div", {
              className: "age-preview",
              children: [
                (0, C.jsx)(nt, { size: 19 }),
                (0, C.jsxs)("div", {
                  children: [
                    (0, C.jsx)("span", {
                      children: "Age, automatically calculated",
                    }),
                    (0, C.jsx)("strong", {
                      children:
                        l && ka(l) !== null
                          ? `${ka(l)} years old`
                          : "Add a birthday to see their age",
                    }),
                  ],
                }),
              ],
            }),
            (0, C.jsx)(E, {
              label: "Email address",
              children: (0, C.jsx)("input", {
                name: "email",
                type: "email",
                maxLength: 300,
                defaultValue: e?.email,
                placeholder: "hello@example.com",
                autoComplete: "email",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Phone number",
              children: (0, C.jsx)("input", {
                name: "phone",
                type: "tel",
                maxLength: 60,
                defaultValue: e?.phone,
                placeholder: "+1 (555) 000-0000",
                autoComplete: "tel",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Address",
              hint: "Optional \u2014 a place to send a little something.",
              full: !0,
              children: (0, C.jsx)("input", {
                name: "address",
                maxLength: 600,
                defaultValue: e?.address,
                placeholder: "Street, city, state, postal code",
                autoComplete: "street-address",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Notes",
              full: !0,
              children: (0, C.jsx)("textarea", {
                name: "notes",
                rows: 3,
                maxLength: 1e4,
                defaultValue: e?.notes,
                placeholder:
                  "Coffee order, how you met, things worth remembering\u2026",
              }),
            }),
          ],
        }),
        (0, C.jsxs)("label", {
          className: "check-label",
          children: [
            (0, C.jsx)("input", {
              type: "checkbox",
              name: "favorite",
              defaultChecked: e?.favorite,
            }),
            (0, C.jsx)(ua, { size: 15 }),
            " Add to favorites",
          ],
        }),
        (0, C.jsx)(rt, { error: f }),
        (0, C.jsxs)("div", {
          className: "form-footer",
          children: [
            (0, C.jsx)("button", {
              type: "button",
              className: "button secondary",
              onClick: t,
              children: "Cancel",
            }),
            (0, C.jsx)(Bu, {
              busy: o,
              children: e ? "Save changes" : "Add contact",
            }),
          ],
        }),
        (0, C.jsx)(Dl, {}),
      ],
    });
  }
  function lg({ account: e, people: a, contactId: t, onDone: l, onClose: u }) {
    let [o, d] = (0, Ye.useState)(e?.type || "Online account"),
      [f, n] = (0, Ye.useState)(!1),
      [p, h] = (0, Ye.useState)(e?.password || ""),
      [x, m] = (0, Ye.useState)(!1),
      [L, k] = (0, Ye.useState)("");
    async function w(c) {
      (c.preventDefault(), m(!0), k(""));
      try {
        (await De("saveAccount", {
          ...Object.fromEntries(new FormData(c.currentTarget)),
          id: e?.id,
          type: o,
          password: p,
        }),
          await l(),
          u());
      } catch (r) {
        k(r.message);
      } finally {
        m(!1);
      }
    }
    function A() {
      let c = crypto.getRandomValues(new Uint8Array(24));
      (h(
        Array.from(
          c,
          (r) =>
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*"[
              r % 69
            ],
        ).join(""),
      ),
        n(!0));
    }
    return (0, C.jsxs)("form", {
      onSubmit: w,
      className: "editor-form",
      children: [
        (0, C.jsx)("div", {
          className: "type-options",
          children: [
            { label: "Online account", icon: bl },
            { label: "Bank account", icon: Al },
          ].map(({ label: c, icon: r }) =>
            (0, C.jsxs)(
              "button",
              {
                type: "button",
                onClick: () => d(c),
                className: `type-option ${o === c ? "selected" : ""}`,
                children: [
                  (0, C.jsx)(r, { size: 20 }),
                  (0, C.jsx)("span", { children: c }),
                  (0, C.jsx)("i", {}),
                ],
              },
              c,
            ),
          ),
        }),
        (0, C.jsxs)("div", {
          className: "form-grid",
          children: [
            (0, C.jsx)(E, {
              label: "Belongs to *",
              children: (0, C.jsxs)("select", {
                required: !0,
                name: "contactId",
                defaultValue: e?.contactId || t || "",
                children: [
                  (0, C.jsx)("option", {
                    value: "",
                    disabled: !0,
                    children: "Select a person",
                  }),
                  a.map((c) =>
                    (0, C.jsx)(
                      "option",
                      { value: c.id, children: c.name },
                      c.id,
                    ),
                  ),
                ],
              }),
            }),
            (0, C.jsx)(E, {
              label: "Account name *",
              children: (0, C.jsx)("input", {
                name: "title",
                required: !0,
                maxLength: 120,
                defaultValue: e?.title,
                placeholder:
                  o === "Bank account"
                    ? "e.g. Everyday checking"
                    : "e.g. Personal email",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Username",
              children: (0, C.jsx)("input", {
                name: "username",
                maxLength: 300,
                defaultValue: e?.username,
                placeholder: "Username",
                autoComplete: "off",
              }),
            }),
            (0, C.jsx)(E, {
              label: "Email address",
              children: (0, C.jsx)("input", {
                name: "email",
                type: "email",
                maxLength: 300,
                defaultValue: e?.email,
                placeholder: "hello@example.com",
                autoComplete: "off",
              }),
            }),
            (0, C.jsxs)(E, {
              label: "Password",
              full: !0,
              children: [
                (0, C.jsxs)("div", {
                  className: "password-input",
                  children: [
                    (0, C.jsx)("input", {
                      "aria-label": "Account password",
                      type: f ? "text" : "password",
                      value: p,
                      onChange: (c) => h(c.target.value),
                      maxLength: 500,
                      placeholder: "Account password",
                      autoComplete: "new-password",
                    }),
                    (0, C.jsx)("button", {
                      type: "button",
                      className: "icon-button",
                      "aria-label": f ? "Hide password" : "Show password",
                      onClick: () => n(!f),
                      children: f
                        ? (0, C.jsx)(Ua, { size: 17 })
                        : (0, C.jsx)(Ha, { size: 17 }),
                    }),
                  ],
                }),
                (0, C.jsxs)("button", {
                  type: "button",
                  className: "text-button generate-password",
                  onClick: A,
                  children: [
                    (0, C.jsx)(Kt, { size: 13 }),
                    " Generate a strong password",
                  ],
                }),
              ],
            }),
            o === "Bank account" &&
              (0, C.jsxs)(C.Fragment, {
                children: [
                  (0, C.jsx)(E, {
                    label: "US routing number *",
                    hint: "Exactly 9 digits",
                    children: (0, C.jsx)("input", {
                      name: "routingNumber",
                      required: !0,
                      pattern: "[0-9]{9}",
                      maxLength: 9,
                      inputMode: "numeric",
                      defaultValue: e?.routingNumber,
                      placeholder: "000000000",
                      autoComplete: "off",
                    }),
                  }),
                  (0, C.jsx)(E, {
                    label: "Account number *",
                    hint: "4\u201334 digits",
                    children: (0, C.jsx)("input", {
                      name: "accountNumber",
                      required: !0,
                      pattern: "[0-9]{4,34}",
                      maxLength: 34,
                      inputMode: "numeric",
                      defaultValue: e?.accountNumber,
                      placeholder: "Account number",
                      autoComplete: "off",
                    }),
                  }),
                ],
              }),
            (0, C.jsx)(E, {
              label: "Notes",
              full: !0,
              children: (0, C.jsx)("textarea", {
                name: "notes",
                rows: 3,
                maxLength: 1e4,
                defaultValue: e?.notes,
                placeholder:
                  "Recovery details, reminders, or anything else to keep safe\u2026",
              }),
            }),
          ],
        }),
        (0, C.jsxs)("div", {
          className: "soft-notice",
          children: [
            (0, C.jsx)(Xe, { size: 18 }),
            (0, C.jsx)("span", {
              children:
                "Passwords, banking details, and account notes are encrypted at rest. Only save information you have permission to store.",
            }),
          ],
        }),
        (0, C.jsx)(rt, { error: L }),
        (0, C.jsxs)("div", {
          className: "form-footer",
          children: [
            (0, C.jsx)("button", {
              className: "button secondary",
              type: "button",
              onClick: u,
              children: "Cancel",
            }),
            (0, C.jsx)(Bu, {
              busy: x,
              children: e ? "Save changes" : "Save account",
            }),
          ],
        }),
      ],
    });
  }
  function ug({ entry: e, people: a, contactId: t, onDone: l, onClose: u }) {
    let [o, d] = (0, Ye.useState)(e?.type || "Reflection"),
      [f, n] = (0, Ye.useState)(e?.contactId || t || ""),
      [p, h] = (0, Ye.useState)(e?.date || Ns()),
      [x, m] = (0, Ye.useState)(!1),
      [L, k] = (0, Ye.useState)(""),
      w = a.find((r) => r.id === f),
      A = !!w?.dob && (ka(w.dob, new Date(`${p}T12:00:00`)) ?? 0) >= 18;
    async function c(r) {
      (r.preventDefault(), m(!0), k(""));
      let g = Object.fromEntries(new FormData(r.currentTarget));
      try {
        (await De("saveJournal", {
          ...g,
          id: e?.id,
          type: o,
          contactId: f,
          date: p,
          adults: g.adults === "on",
        }),
          await l(),
          u());
      } catch (y) {
        k(y.message);
      } finally {
        m(!1);
      }
    }
    return (0, C.jsxs)("form", {
      onSubmit: c,
      className: "editor-form",
      children: [
        (0, C.jsx)("div", {
          className: "type-options",
          children: [
            {
              label: "Reflection",
              icon: Zt,
              description: "Moments worth remembering",
            },
            {
              label: "Intimacy",
              icon: ua,
              description: "A thoughtful, private check-in",
            },
          ].map(({ label: r, icon: g, description: y }) =>
            (0, C.jsxs)(
              "button",
              {
                type: "button",
                onClick: () => d(r),
                className: `type-option ${o === r ? "selected" : ""}`,
                children: [
                  (0, C.jsx)(g, { size: 20 }),
                  (0, C.jsxs)("span", {
                    children: [r, (0, C.jsx)("small", { children: y })],
                  }),
                  (0, C.jsx)("i", {}),
                ],
              },
              r,
            ),
          ),
        }),
        (0, C.jsxs)("div", {
          className: "form-grid",
          children: [
            (0, C.jsx)(E, {
              label: "Who is this about? *",
              children: (0, C.jsxs)("select", {
                required: !0,
                value: f,
                onChange: (r) => n(r.target.value),
                children: [
                  (0, C.jsx)("option", {
                    value: "",
                    disabled: !0,
                    children: "Select a person",
                  }),
                  a.map((r) =>
                    (0, C.jsx)(
                      "option",
                      { value: r.id, children: r.name },
                      r.id,
                    ),
                  ),
                ],
              }),
            }),
            (0, C.jsx)(E, {
              label: "When? *",
              children: (0, C.jsx)("input", {
                type: "date",
                required: !0,
                value: p,
                max: Ns(),
                onChange: (r) => h(r.target.value),
              }),
            }),
            (0, C.jsx)(E, {
              label: "How are you feeling?",
              full: !0,
              children: (0, C.jsxs)("select", {
                name: "mood",
                defaultValue: e?.mood || "Connected",
                children: [
                  (0, C.jsx)("option", { children: "Connected" }),
                  (0, C.jsx)("option", { children: "Happy" }),
                  (0, C.jsx)("option", { children: "Reflective" }),
                  (0, C.jsx)("option", { children: "Neutral" }),
                  (0, C.jsx)("option", { children: "Uncertain" }),
                  (0, C.jsx)("option", { children: "Difficult" }),
                ],
              }),
            }),
            o === "Intimacy" &&
              (0, C.jsxs)(C.Fragment, {
                children: [
                  (0, C.jsxs)("div", {
                    className: "journal-section field-full",
                    children: [
                      (0, C.jsx)("span", {
                        className: "section-icon",
                        children: (0, C.jsx)(ua, { size: 17 }),
                      }),
                      (0, C.jsxs)("div", {
                        children: [
                          (0, C.jsx)("h3", {
                            children: "A little space to check in",
                          }),
                          (0, C.jsx)("p", {
                            children:
                              "For adults 18+ only. Reflect on consent, care, and your wellbeing.",
                          }),
                        ],
                      }),
                    ],
                  }),
                  !A &&
                    (0, C.jsx)("div", {
                      className: "form-warning field-full",
                      children:
                        "This person needs a date of birth confirming they were 18 or older on the entry date. You can add it in their contact details.",
                    }),
                  (0, C.jsxs)("label", {
                    className: "check-label field-full",
                    children: [
                      (0, C.jsx)("input", {
                        type: "checkbox",
                        name: "adults",
                        required: !0,
                        defaultChecked: e?.adults,
                      }),
                      " I confirm that both of us were 18 or older on this date.",
                    ],
                  }),
                  (0, C.jsx)(E, {
                    label: "Was consent freely given, clear, and ongoing? *",
                    full: !0,
                    children: (0, C.jsxs)("select", {
                      name: "consent",
                      required: !0,
                      defaultValue: e?.consent || "",
                      children: [
                        (0, C.jsx)("option", {
                          value: "",
                          disabled: !0,
                          children: "Choose a response",
                        }),
                        (0, C.jsx)("option", { children: "Yes" }),
                        (0, C.jsx)("option", { children: "No" }),
                        (0, C.jsx)("option", {
                          children: "Unsure \u2014 I need to reflect on this",
                        }),
                      ],
                    }),
                  }),
                  (0, C.jsx)(E, {
                    label: "Did you discuss boundaries and communicate openly?",
                    full: !0,
                    children: (0, C.jsx)("textarea", {
                      name: "communication",
                      rows: 2,
                      maxLength: 1e3,
                      defaultValue: e?.communication,
                      placeholder:
                        "What felt comfortable? Was anything left unsaid?",
                    }),
                  }),
                  (0, C.jsx)(E, {
                    label: "Were protection and sexual health discussed?",
                    full: !0,
                    children: (0, C.jsxs)("select", {
                      name: "protection",
                      defaultValue: e?.protection || "",
                      children: [
                        (0, C.jsx)("option", {
                          value: "",
                          children: "Prefer not to record",
                        }),
                        (0, C.jsx)("option", {
                          children: "Discussed; protection used",
                        }),
                        (0, C.jsx)("option", {
                          children: "Discussed; protection not used",
                        }),
                        (0, C.jsx)("option", { children: "Not discussed" }),
                        (0, C.jsx)("option", { children: "Not applicable" }),
                      ],
                    }),
                  }),
                  (0, C.jsx)(E, {
                    label:
                      "How did you feel emotionally and physically afterward?",
                    full: !0,
                    children: (0, C.jsx)("textarea", {
                      name: "wellbeing",
                      rows: 2,
                      maxLength: 1e3,
                      defaultValue: e?.wellbeing,
                      placeholder:
                        "A kind, honest check-in with yourself\u2026",
                    }),
                  }),
                  (0, C.jsx)(E, {
                    label:
                      "Is there any aftercare or follow-up you would like?",
                    full: !0,
                    children: (0, C.jsx)("textarea", {
                      name: "followUp",
                      rows: 2,
                      maxLength: 1e3,
                      defaultValue: e?.followUp,
                      placeholder:
                        "A conversation, a health check-in, time together\u2026",
                    }),
                  }),
                ],
              }),
            (0, C.jsx)(E, {
              label:
                o === "Intimacy"
                  ? "Your private reflection *"
                  : "What would you like to remember? *",
              full: !0,
              children: (0, C.jsx)("textarea", {
                name: "notes",
                required: !0,
                rows: 4,
                maxLength: 1e4,
                defaultValue: e?.notes,
                placeholder:
                  "No perfect words needed. This space is just for you.",
              }),
            }),
          ],
        }),
        o === "Intimacy" &&
          (0, C.jsx)("p", {
            className: "journal-help",
            children:
              "If an experience felt unsafe or non-consensual, you deserve support. Consider speaking with someone you trust or a qualified support service.",
          }),
        (0, C.jsx)(rt, { error: L }),
        (0, C.jsxs)("div", {
          className: "form-footer",
          children: [
            (0, C.jsx)("button", {
              className: "button secondary",
              type: "button",
              onClick: u,
              children: "Cancel",
            }),
            (0, C.jsx)(Bu, {
              busy: x,
              disabled: o === "Intimacy" && !A,
              children: e ? "Save changes" : "Save privately",
            }),
          ],
        }),
        (0, C.jsxs)(Dl, {
          children: [
            (0, C.jsx)(ne, { size: 12, className: "inline-icon" }),
            " Journal content is encrypted at rest and never included in contact exports.",
          ],
        }),
      ],
    });
  }
  var Pa = ce(ya());
  var S = ce(da());
  function og({ onClose: e, onDone: a }) {
    let t = typeof window < "u" && !!window.__PHP__,
      [l, u] = (0, Pa.useState)(0),
      [o, d] = (0, Pa.useState)(!1),
      [f, n] = (0, Pa.useState)(""),
      [p, h] = (0, Pa.useState)(!1),
      [x, m] = (0, Pa.useState)({
        ownerName: "",
        ownerEmail: "",
        name: "My personal space",
        password: "",
        confirm: "",
        journalEnabled: !0,
        isAdult: !1,
        keepSample: !t,
        mysqlBackup: !1,
        mysqlHost: "127.0.0.1",
        mysqlPort: "3306",
        mysqlDatabase: "innercircle_backup",
        mysqlUsername: "",
        mysqlPassword: "",
      }),
      L = (A, c) => m((r) => ({ ...r, [A]: c }));
    async function k(A) {
      if ((A.preventDefault(), n(""), l === 1 && x.password !== x.confirm)) {
        n("Your passwords don\u2019t match yet.");
        return;
      }
      if (l < 2) {
        u((c) => c + 1);
        return;
      }
      d(!0);
      try {
        (await De("setup", x), await a(), u(3));
      } catch (c) {
        n(c.message);
      } finally {
        d(!1);
      }
    }
    function w() {
      t && window.__INSTALL__ ? (window.location.href = "../index.php") : e();
    }
    return (0, S.jsx)(Ma, {
      title:
        l === 3
          ? "Welcome to your inner circle."
          : [
              "A space for your people.",
              "Personal means private.",
              "A few finishing touches.",
            ][l],
      subtitle:
        l === 3
          ? "Your workspace is ready. Make yourself at home."
          : "A thoughtful little setup. Then it\u2019s all yours.",
      onClose: e,
      wide: !0,
      children:
        l < 3
          ? (0, S.jsxs)("form", {
              onSubmit: k,
              className: "setup-form",
              children: [
                (0, S.jsx)("div", {
                  className: "setup-steps",
                  children: [
                    "Your space",
                    "Your privacy",
                    "Your preferences",
                  ].map((A, c) =>
                    (0, S.jsxs)(
                      "div",
                      {
                        className: l >= c ? "active" : "",
                        children: [
                          (0, S.jsx)("span", {
                            children:
                              l > c ? (0, S.jsx)(la, { size: 13 }) : c + 1,
                          }),
                          A,
                        ],
                      },
                      A,
                    ),
                  ),
                }),
                l === 0 &&
                  (0, S.jsxs)("div", {
                    className: "setup-content",
                    children: [
                      (0, S.jsxs)("div", {
                        className: "setup-illustration",
                        children: [
                          (0, S.jsx)(kl, { size: 38, strokeWidth: 1.2 }),
                          (0, S.jsx)("span", {
                            children:
                              "A little more connected. A little more you.",
                          }),
                        ],
                      }),
                      (0, S.jsxs)("div", {
                        className: "form-grid",
                        children: [
                          (0, S.jsx)(E, {
                            label: "What should we call you? *",
                            children: (0, S.jsx)("input", {
                              required: !0,
                              maxLength: 100,
                              autoComplete: "name",
                              value: x.ownerName,
                              onChange: (A) => L("ownerName", A.target.value),
                              placeholder: "Your name",
                            }),
                          }),
                          (0, S.jsx)(E, {
                            label: "Your email address *",
                            children: (0, S.jsx)("input", {
                              required: !0,
                              type: "email",
                              autoComplete: "email",
                              value: x.ownerEmail,
                              onChange: (A) => L("ownerEmail", A.target.value),
                              placeholder: "you@example.com",
                            }),
                          }),
                          (0, S.jsx)(E, {
                            label: "Give your workspace a name *",
                            full: !0,
                            children: (0, S.jsx)("input", {
                              required: !0,
                              maxLength: 100,
                              value: x.name,
                              onChange: (A) => L("name", A.target.value),
                            }),
                          }),
                        ],
                      }),
                      (0, S.jsx)("p", {
                        className: "fine-print",
                        children:
                          "Your email is saved locally as your owner profile. We don\u2019t send marketing emails or offer email-based password recovery.",
                      }),
                    ],
                  }),
                l === 1 &&
                  (0, S.jsxs)("div", {
                    className: "setup-content",
                    children: [
                      (0, S.jsxs)("div", {
                        className: "soft-notice",
                        children: [
                          (0, S.jsx)(Xe, { size: 20 }),
                          (0, S.jsx)("span", {
                            children:
                              "One owner. One private workspace. Your account passwords, bank details, and journal content will be encrypted.",
                          }),
                        ],
                      }),
                      (0, S.jsxs)("div", {
                        className: "form-grid",
                        children: [
                          (0, S.jsx)(E, {
                            label: "Create your workspace password *",
                            hint: "At least 12 characters. A memorable passphrase works well.",
                            full: !0,
                            children: (0, S.jsxs)("div", {
                              className: "password-input",
                              children: [
                                (0, S.jsx)("input", {
                                  required: !0,
                                  minLength: 12,
                                  maxLength: 256,
                                  type: p ? "text" : "password",
                                  autoComplete: "new-password",
                                  value: x.password,
                                  onChange: (A) =>
                                    L("password", A.target.value),
                                }),
                                (0, S.jsx)("button", {
                                  type: "button",
                                  className: "icon-button",
                                  "aria-label": p
                                    ? "Hide password"
                                    : "Show password",
                                  onClick: () => h(!p),
                                  children: p
                                    ? (0, S.jsx)(Ua, { size: 17 })
                                    : (0, S.jsx)(Ha, { size: 17 }),
                                }),
                              ],
                            }),
                          }),
                          (0, S.jsx)(E, {
                            label: "Confirm your password *",
                            full: !0,
                            children: (0, S.jsx)("input", {
                              required: !0,
                              minLength: 12,
                              type: "password",
                              autoComplete: "new-password",
                              value: x.confirm,
                              onChange: (A) => L("confirm", A.target.value),
                            }),
                          }),
                        ],
                      }),
                      (0, S.jsxs)("label", {
                        className: "setting-toggle",
                        children: [
                          (0, S.jsxs)("span", {
                            children: [
                              (0, S.jsxs)("strong", {
                                children: [
                                  (0, S.jsx)(ua, { size: 16 }),
                                  " Enable the private journal",
                                ],
                              }),
                              (0, S.jsx)("small", {
                                children:
                                  "A place for personal reflections and adult intimacy check-ins.",
                              }),
                            ],
                          }),
                          (0, S.jsx)("input", {
                            className: "switch",
                            type: "checkbox",
                            checked: x.journalEnabled,
                            onChange: (A) =>
                              L("journalEnabled", A.target.checked),
                          }),
                        ],
                      }),
                      x.journalEnabled &&
                        (0, S.jsxs)("label", {
                          className: "check-label",
                          children: [
                            (0, S.jsx)("input", {
                              required: !0,
                              type: "checkbox",
                              checked: x.isAdult,
                              onChange: (A) => L("isAdult", A.target.checked),
                            }),
                            " I am 18 or older. Intimacy entries will only involve adults.",
                          ],
                        }),
                      (0, S.jsx)("p", {
                        className: "fine-print",
                        children:
                          "Keep your password somewhere safe. This self-hosted workspace has no remote password-reset service.",
                      }),
                    ],
                  }),
                l === 2 &&
                  (0, S.jsxs)("div", {
                    className: "setup-content",
                    children: [
                      (0, S.jsxs)("div", {
                        className: "storage-option",
                        children: [
                          (0, S.jsx)(Gt, { size: 25 }),
                          (0, S.jsxs)("div", {
                            children: [
                              (0, S.jsx)("strong", {
                                children: t
                                  ? "SQLite"
                                  : "MySQL \xB7 live preview",
                              }),
                              (0, S.jsx)("p", {
                                children: t
                                  ? "A simple, local database. No database server needed."
                                  : "This live preview uses MySQL. The downloadable PHP edition uses SQLite with optional MySQL backup.",
                              }),
                            ],
                          }),
                          (0, S.jsxs)("span", {
                            className: "status-tag",
                            children: [
                              (0, S.jsx)(la, { size: 12 }),
                              " ",
                              t ? "Recommended" : "Connected",
                            ],
                          }),
                        ],
                      }),
                      t &&
                        (0, S.jsxs)(S.Fragment, {
                          children: [
                            (0, S.jsxs)("label", {
                              className: "setting-toggle",
                              children: [
                                (0, S.jsxs)("span", {
                                  children: [
                                    (0, S.jsx)("strong", {
                                      children:
                                        "Connect a MySQL backup database",
                                    }),
                                    (0, S.jsx)("small", {
                                      children:
                                        "Optional. Keep an on-demand second copy of your SQLite data.",
                                    }),
                                  ],
                                }),
                                (0, S.jsx)("input", {
                                  className: "switch",
                                  type: "checkbox",
                                  checked: x.mysqlBackup,
                                  onChange: (A) =>
                                    L("mysqlBackup", A.target.checked),
                                }),
                              ],
                            }),
                            x.mysqlBackup &&
                              (0, S.jsxs)("div", {
                                className: "form-grid",
                                children: [
                                  (0, S.jsx)(E, {
                                    label: "MySQL host",
                                    children: (0, S.jsx)("input", {
                                      required: !0,
                                      value: x.mysqlHost,
                                      onChange: (A) =>
                                        L("mysqlHost", A.target.value),
                                    }),
                                  }),
                                  (0, S.jsx)(E, {
                                    label: "Port",
                                    children: (0, S.jsx)("input", {
                                      required: !0,
                                      type: "number",
                                      min: 1,
                                      max: 65535,
                                      value: x.mysqlPort,
                                      onChange: (A) =>
                                        L("mysqlPort", A.target.value),
                                    }),
                                  }),
                                  (0, S.jsx)(E, {
                                    label: "Existing database name",
                                    children: (0, S.jsx)("input", {
                                      required: !0,
                                      pattern: "[a-zA-Z0-9_]+",
                                      value: x.mysqlDatabase,
                                      onChange: (A) =>
                                        L("mysqlDatabase", A.target.value),
                                    }),
                                  }),
                                  (0, S.jsx)(E, {
                                    label: "Database username",
                                    children: (0, S.jsx)("input", {
                                      required: !0,
                                      value: x.mysqlUsername,
                                      onChange: (A) =>
                                        L("mysqlUsername", A.target.value),
                                    }),
                                  }),
                                  (0, S.jsx)(E, {
                                    label: "Database password",
                                    full: !0,
                                    children: (0, S.jsx)("input", {
                                      type: "password",
                                      autoComplete: "new-password",
                                      value: x.mysqlPassword,
                                      onChange: (A) =>
                                        L("mysqlPassword", A.target.value),
                                    }),
                                  }),
                                ],
                              }),
                          ],
                        }),
                      (0, S.jsxs)("label", {
                        className: "setting-toggle",
                        children: [
                          (0, S.jsxs)("span", {
                            children: [
                              (0, S.jsx)("strong", {
                                children: "Start with a little inspiration",
                              }),
                              (0, S.jsx)("small", {
                                children:
                                  "Include eight fictional sample contacts. You can edit or delete them.",
                              }),
                            ],
                          }),
                          (0, S.jsx)("input", {
                            className: "switch",
                            type: "checkbox",
                            checked: x.keepSample,
                            onChange: (A) => L("keepSample", A.target.checked),
                          }),
                        ],
                      }),
                      (0, S.jsxs)("div", {
                        className: "soft-notice",
                        children: [
                          (0, S.jsx)(Yt, { size: 21 }),
                          (0, S.jsxs)("span", {
                            children: [
                              (0, S.jsx)("strong", {
                                children: "Installation closes behind you.",
                              }),
                              (0, S.jsx)("br", {}),
                              "A persistent installation lock blocks setup after this step.",
                              t &&
                                " You can also delete or rename the install folder.",
                              " Your workspace automatically locks after 15 minutes of inactivity.",
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                (0, S.jsx)(rt, { error: f }),
                (0, S.jsxs)("div", {
                  className: "form-footer",
                  children: [
                    l > 0
                      ? (0, S.jsxs)("button", {
                          className: "button secondary",
                          type: "button",
                          onClick: () => u((A) => A - 1),
                          disabled: o,
                          children: [(0, S.jsx)(No, { size: 15 }), " Back"],
                        })
                      : (0, S.jsx)("span", {
                          className: "fine-print",
                          children: "About 2 minutes. Yours for keeps.",
                        }),
                    (0, S.jsxs)("button", {
                      className: "button primary",
                      disabled: o,
                      type: "submit",
                      children: [
                        o
                          ? (0, S.jsx)(Re, { size: 16, className: "spin" })
                          : l === 2
                            ? (0, S.jsx)(ne, { size: 16 })
                            : null,
                        o
                          ? "Creating your space\u2026"
                          : l === 2
                            ? "Create my workspace"
                            : "Continue",
                        l < 2 && (0, S.jsx)(Ca, { size: 15 }),
                      ],
                    }),
                  ],
                }),
              ],
            })
          : (0, S.jsxs)("div", {
              className: "setup-success",
              children: [
                (0, S.jsx)("span", {
                  className: "success-icon",
                  children: (0, S.jsx)(la, { size: 36 }),
                }),
                (0, S.jsx)("h3", { children: "All yours. Just yours." }),
                (0, S.jsx)("p", {
                  children:
                    "Your owner account is created, your privacy preferences are saved, and installation is now locked.",
                }),
                (0, S.jsxs)("div", {
                  className: "success-list",
                  children: [
                    (0, S.jsxs)("span", {
                      children: [
                        (0, S.jsx)(Xe, { size: 17 }),
                        " Password protection enabled",
                      ],
                    }),
                    (0, S.jsxs)("span", {
                      children: [
                        (0, S.jsx)(Yt, { size: 17 }),
                        " Installation lock created",
                      ],
                    }),
                    (0, S.jsxs)("span", {
                      children: [
                        (0, S.jsx)(Gt, { size: 17 }),
                        " ",
                        t ? "SQLite database ready" : "MySQL database ready",
                      ],
                    }),
                  ],
                }),
                (0, S.jsxs)("button", {
                  className: "button primary",
                  onClick: w,
                  children: [
                    "Meet your inner circle ",
                    (0, S.jsx)(Ca, { size: 16 }),
                  ],
                }),
              ],
            }),
    });
  }
  function dg({ onUnlock: e }) {
    let [a, t] = (0, Pa.useState)(""),
      [l, u] = (0, Pa.useState)(!1),
      [o, d] = (0, Pa.useState)(!1),
      [f, n] = (0, Pa.useState)("");
    async function p(h) {
      (h.preventDefault(), d(!0), n(""));
      try {
        (await De("login", { password: a }), await e());
      } catch (x) {
        n(x.message);
      } finally {
        d(!1);
      }
    }
    return (0, S.jsxs)("main", {
      className: "lock-page",
      children: [
        (0, S.jsx)("div", {
          className: "lock-brand",
          children: (0, S.jsx)(Jo, {}),
        }),
        (0, S.jsxs)("div", {
          className: "lock-card",
          children: [
            (0, S.jsx)("div", {
              className: "lock-emblem",
              children: (0, S.jsx)(ne, { size: 30, strokeWidth: 1.4 }),
            }),
            (0, S.jsx)("p", {
              className: "eyebrow",
              children: "YOUR PRIVATE CORNER OF THE WORLD",
            }),
            (0, S.jsx)("h1", { children: "A little privacy, please." }),
            (0, S.jsxs)("p", {
              children: [
                "Your people and your memories.",
                (0, S.jsx)("br", {}),
                "Right where you left them.",
              ],
            }),
            (0, S.jsxs)("form", {
              onSubmit: p,
              children: [
                (0, S.jsx)(E, {
                  label: "Workspace password",
                  children: (0, S.jsxs)("div", {
                    className: "password-input",
                    children: [
                      (0, S.jsx)("input", {
                        type: l ? "text" : "password",
                        required: !0,
                        autoFocus: !0,
                        autoComplete: "current-password",
                        value: a,
                        onChange: (h) => t(h.target.value),
                        placeholder: "Enter your password",
                      }),
                      (0, S.jsx)("button", {
                        type: "button",
                        className: "icon-button",
                        onClick: () => u(!l),
                        "aria-label": l ? "Hide password" : "Show password",
                        children: l
                          ? (0, S.jsx)(Ua, { size: 17 })
                          : (0, S.jsx)(Ha, { size: 17 }),
                      }),
                    ],
                  }),
                }),
                (0, S.jsx)(rt, { error: f }),
                (0, S.jsxs)("button", {
                  className: "button primary",
                  disabled: o,
                  children: [
                    o
                      ? (0, S.jsx)(Re, { size: 16, className: "spin" })
                      : (0, S.jsx)(ne, { size: 16 }),
                    " ",
                    o ? "Unlocking\u2026" : "Unlock my workspace",
                  ],
                }),
              ],
            }),
            (0, S.jsxs)("span", {
              className: "lock-caption",
              children: [
                (0, S.jsx)(Xe, { size: 13 }),
                " Your life isn\u2019t public. Neither is this.",
              ],
            }),
          ],
        }),
        (0, S.jsx)("p", {
          className: "lock-footer",
          children: "A little closer to the people who matter.",
        }),
      ],
    });
  }
  var $o = ce(ya());
  var b = ce(da());
  function sg({
    data: e,
    theme: a,
    onThemeChange: t,
    onDone: l,
    onSetup: u,
    notify: o,
  }) {
    let [d, f] = (0, $o.useState)(!1),
      [n, p] = (0, $o.useState)(!1),
      [h, x] = (0, $o.useState)(""),
      [m, L] = (0, $o.useState)(e.journalEnabled),
      k = typeof window < "u" && !!window.__PHP__;
    async function w(c) {
      if ((c.preventDefault(), !e.installed)) {
        u();
        return;
      }
      (f(!0), x(""));
      let r = Object.fromEntries(new FormData(c.currentTarget));
      try {
        (await De("updateSettings", {
          ...r,
          journalEnabled: m,
          isAdult: r.isAdult === "on",
          autoLockMinutes: Number(r.autoLockMinutes),
        }),
          await l(),
          o("Your preferences have been saved."));
      } catch (g) {
        x(g.message);
      } finally {
        f(!1);
      }
    }
    async function A(c = !1) {
      if (!e.installed) {
        u();
        return;
      }
      p(!0);
      try {
        let r = await De(c ? "mysqlBackup" : "backup");
        (c ||
          Ps(
            `innercircle-backup-${new Date().toISOString().slice(0, 10)}.json`,
            JSON.stringify(r.backup, null, 2),
          ),
          o(
            c
              ? "Your encrypted data was backed up to MySQL."
              : "Backup downloaded. Keep your encryption key safe and separate.",
          ));
      } catch (r) {
        o(r.message);
      } finally {
        p(!1);
      }
    }
    return (0, b.jsxs)("div", {
      className: "settings-layout",
      children: [
        (0, b.jsxs)("form", {
          onSubmit: w,
          className: "settings-main",
          children: [
            !e.installed &&
              (0, b.jsxs)("div", {
                className: "setup-banner",
                children: [
                  (0, b.jsx)("span", {
                    className: "setup-banner-icon",
                    children: (0, b.jsx)(Cy, {}),
                  }),
                  (0, b.jsxs)("div", {
                    children: [
                      (0, b.jsx)("h3", {
                        children: "Make this little space yours.",
                      }),
                      (0, b.jsx)("p", {
                        children:
                          "You\u2019re exploring a demo. Finish setup to password-protect your workspace and enable private features.",
                      }),
                    ],
                  }),
                  (0, b.jsxs)("button", {
                    type: "button",
                    className: "button primary",
                    onClick: u,
                    children: ["Finish setup ", (0, b.jsx)(Na, { size: 15 })],
                  }),
                ],
              }),
            (0, b.jsxs)("section", {
              className: "settings-card",
              children: [
                (0, b.jsxs)("div", {
                  className: "settings-card-title",
                  children: [
                    (0, b.jsx)(wl, { size: 19 }),
                    (0, b.jsxs)("div", {
                      children: [
                        (0, b.jsx)("h3", { children: "Your workspace" }),
                        (0, b.jsx)("p", {
                          children:
                            "The familiar details that make this place yours.",
                        }),
                      ],
                    }),
                  ],
                }),
                (0, b.jsxs)("div", {
                  className: "form-grid",
                  children: [
                    (0, b.jsx)(E, {
                      label: "Your name",
                      children: (0, b.jsx)("input", {
                        name: "ownerName",
                        required: !0,
                        maxLength: 100,
                        defaultValue: e.ownerName,
                      }),
                    }),
                    (0, b.jsx)(E, {
                      label: "Your email",
                      children: (0, b.jsx)("input", {
                        name: "ownerEmail",
                        type: "email",
                        required: !0,
                        defaultValue: e.ownerEmail,
                        placeholder: "you@example.com",
                      }),
                    }),
                    (0, b.jsx)(E, {
                      label: "Workspace name",
                      full: !0,
                      children: (0, b.jsx)("input", {
                        name: "name",
                        required: !0,
                        maxLength: 100,
                        defaultValue: e.name,
                      }),
                    }),
                  ],
                }),
              ],
            }),
            (0, b.jsxs)("section", {
              className: "settings-card",
              children: [
                (0, b.jsxs)("div", {
                  className: "settings-card-title",
                  children: [
                    (0, b.jsx)(Tu, { size: 19 }),
                    (0, b.jsxs)("div", {
                      children: [
                        (0, b.jsx)("h3", { children: "Appearance" }),
                        (0, b.jsx)("p", {
                          children:
                            "Choose a comfortable look for your workspace.",
                        }),
                      ],
                    }),
                  ],
                }),
                (0, b.jsxs)("button", {
                  type: "button",
                  className: "theme-toggle",
                  "aria-pressed": a === "dark",
                  onClick: () => t(a === "dark" ? "light" : "dark"),
                  children: [
                    (0, b.jsx)("span", {
                      className: "theme-toggle-icon",
                      children:
                        a === "dark"
                          ? (0, b.jsx)(jo, { size: 18 })
                          : (0, b.jsx)(Tu, { size: 18 }),
                    }),
                    (0, b.jsxs)("span", {
                      children: [
                        (0, b.jsx)("strong", {
                          children:
                            a === "dark"
                              ? "Dark mode is on"
                              : "Dark mode is off",
                        }),
                        (0, b.jsx)("small", {
                          children:
                            a === "dark"
                              ? "Switch back to the light appearance."
                              : "Use a darker palette throughout the app.",
                        }),
                      ],
                    }),
                    (0, b.jsx)("span", {
                      className: `theme-switch ${a === "dark" ? "enabled" : ""}`,
                      "aria-hidden": "true",
                      children: (0, b.jsx)("i", {}),
                    }),
                  ],
                }),
              ],
            }),
            (0, b.jsxs)("section", {
              className: "settings-card",
              children: [
                (0, b.jsxs)("div", {
                  className: "settings-card-title",
                  children: [
                    (0, b.jsx)(Xe, { size: 20 }),
                    (0, b.jsxs)("div", {
                      children: [
                        (0, b.jsx)("h3", {
                          children: "Privacy & peace of mind",
                        }),
                        (0, b.jsx)("p", {
                          children: "A few boundaries for your personal world.",
                        }),
                      ],
                    }),
                  ],
                }),
                (0, b.jsx)(E, {
                  label: "Automatically lock after inactivity",
                  children: (0, b.jsxs)("select", {
                    name: "autoLockMinutes",
                    defaultValue: e.autoLockMinutes,
                    children: [
                      (0, b.jsx)("option", { value: 5, children: "5 minutes" }),
                      (0, b.jsx)("option", {
                        value: 15,
                        children: "15 minutes (recommended)",
                      }),
                      (0, b.jsx)("option", {
                        value: 30,
                        children: "30 minutes",
                      }),
                      (0, b.jsx)("option", { value: 60, children: "1 hour" }),
                    ],
                  }),
                }),
                (0, b.jsxs)("label", {
                  className: "setting-toggle",
                  children: [
                    (0, b.jsxs)("span", {
                      children: [
                        (0, b.jsx)("strong", { children: "Private journal" }),
                        (0, b.jsx)("small", {
                          children:
                            "Personal reflections and adult-only intimacy check-ins.",
                        }),
                      ],
                    }),
                    (0, b.jsx)("input", {
                      className: "switch",
                      type: "checkbox",
                      checked: m,
                      onChange: (c) => L(c.target.checked),
                    }),
                  ],
                }),
                m &&
                  !e.journalEnabled &&
                  (0, b.jsxs)("label", {
                    className: "check-label",
                    children: [
                      (0, b.jsx)("input", {
                        type: "checkbox",
                        required: !0,
                        name: "isAdult",
                      }),
                      " I am 18 or older. Intimacy entries will only involve adults.",
                    ],
                  }),
                (0, b.jsxs)("div", {
                  className: "security-status",
                  children: [
                    (0, b.jsx)(Yt, { size: 17 }),
                    (0, b.jsxs)("div", {
                      children: [
                        (0, b.jsx)("strong", {
                          children: e.installed
                            ? "Installation is locked"
                            : "Installation isn\u2019t locked yet",
                        }),
                        (0, b.jsx)("p", {
                          children: e.installed
                            ? "The setup endpoint cannot be used again. Your configuration is protected."
                            : "Complete setup to create an owner account and a persistent installation lock.",
                        }),
                      ],
                    }),
                    e.installed && (0, b.jsx)(la, { size: 17 }),
                  ],
                }),
              ],
            }),
            (0, b.jsx)(rt, { error: h }),
            (0, b.jsx)("div", {
              className: "settings-submit",
              children: (0, b.jsx)(Bu, {
                busy: d,
                children: "Save preferences",
              }),
            }),
          ],
        }),
        (0, b.jsxs)("aside", {
          className: "settings-aside",
          children: [
            (0, b.jsxs)("section", {
              className: "settings-card",
              children: [
                (0, b.jsxs)("div", {
                  className: "settings-card-title",
                  children: [
                    (0, b.jsx)(Gt, { size: 19 }),
                    (0, b.jsxs)("div", {
                      children: [
                        (0, b.jsx)("h3", { children: "Your data, your home" }),
                        (0, b.jsxs)("p", {
                          children: ["Stored in ", e.engine, "."],
                        }),
                      ],
                    }),
                  ],
                }),
                (0, b.jsxs)("div", {
                  className: "storage-status",
                  children: [
                    (0, b.jsx)("span", { className: "online-dot" }),
                    " Database connected ",
                    (0, b.jsx)("span", {
                      className: "status-tag",
                      children: "Local",
                    }),
                  ],
                }),
                (0, b.jsx)("p", {
                  className: "settings-description",
                  children:
                    "Account secrets and journal content are encrypted at rest. Contacts and account labels are stored as regular database records.",
                }),
                (0, b.jsxs)("button", {
                  className: "button secondary full-width",
                  onClick: () => A(),
                  disabled: n,
                  children: [
                    n
                      ? (0, b.jsx)(Re, { className: "spin", size: 15 })
                      : (0, b.jsx)(Vt, { size: 15 }),
                    " Export encrypted backup",
                  ],
                }),
                (0, b.jsx)("p", {
                  className: "fine-print",
                  children:
                    "A backup needs your original encryption key to restore private data. Keep the private data directory or master key separately and securely.",
                }),
                e.mysqlBackup &&
                  (0, b.jsxs)("button", {
                    className: "button secondary full-width",
                    onClick: () => A(!0),
                    disabled: n,
                    children: [
                      (0, b.jsx)(Zo, { size: 15 }),
                      " Back up to MySQL",
                    ],
                  }),
              ],
            }),
            !k &&
              (0, b.jsxs)("section", {
                className: "settings-card php-card",
                children: [
                  (0, b.jsx)("span", {
                    className: "little-label",
                    children: "SELF-HOSTED, YOUR WAY",
                  }),
                  (0, b.jsx)("h3", { children: "A home on your own server." }),
                  (0, b.jsx)("p", {
                    children:
                      "The PHP edition includes the same interface, SQLite storage, optional MySQL backup, and a locked installer.",
                  }),
                  (0, b.jsxs)("a", {
                    className: "button primary full-width",
                    href: "/downloads/innercircle-php.zip",
                    download: !0,
                    children: [
                      (0, b.jsx)(Vt, { size: 15 }),
                      " PHP / XAMPP edition",
                    ],
                  }),
                  (0, b.jsx)("span", {
                    className: "fine-print",
                    children: "PHP 8.5 compatible \xB7 Setup guide included",
                  }),
                ],
              }),
            (0, b.jsxs)("div", {
              className: "settings-note",
              children: [
                (0, b.jsx)(ne, { size: 17 }),
                (0, b.jsx)("p", {
                  children:
                    "Use HTTPS when accessing your workspace over a network. Only store other people\u2019s information with their permission.",
                }),
              ],
            }),
          ],
        }),
      ],
    });
  }
  function Cy() {
    return (0, b.jsx)("svg", {
      width: "26",
      height: "26",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "1.4",
      children: (0, b.jsx)("path", {
        d: "m12 2 2.8 7.2L22 12l-7.2 2.8L12 22l-2.8-7.2L2 12l7.2-2.8L12 2Z",
      }),
    });
  }
  var s = ce(da()),
    yy = {
      Overview: {
        eyebrow: "A LITTLE MORE CONNECTED",
        title: "Good people. Close at hand.",
        description: "A little overview of the people and moments that matter.",
      },
      Contacts: {
        eyebrow: "YOUR PERSONAL ADDRESS BOOK",
        title: "Your people, in one place.",
        description: "The details that matter. The people who do, too.",
      },
      Favorites: {
        eyebrow: "YOUR NEAREST & DEAREST",
        title: "A little closer to your heart.",
        description: "Your favorite people, always within reach.",
      },
      Accounts: {
        eyebrow: "YOUR PRIVATE ACCOUNT VAULT",
        title: "The details, safely kept.",
        description: "A secure home for the accounts connected to your people.",
      },
      "Private journal": {
        eyebrow: "FOR YOUR EYES ONLY",
        title: "A few words, just for you.",
        description: "A quiet space for connection, intimacy, and reflection.",
      },
      Settings: {
        eyebrow: "MAKE YOURSELF AT HOME",
        title: "Your space. Your way.",
        description:
          "A little fine-tuning for your personal corner of the world.",
      },
    };
  function Gi() {
    let [e, a] = (0, q.useState)(WL),
      [t, l] = (0, q.useState)(!1),
      [u, o] = (0, q.useState)("light"),
      [d, f] = (0, q.useState)("Contacts"),
      [n, p] = (0, q.useState)("All contacts"),
      [h, x] = (0, q.useState)(""),
      [m, L] = (0, q.useState)("grid"),
      [k, w] = (0, q.useState)("recent"),
      [A, c] = (0, q.useState)(!1),
      [r, g] = (0, q.useState)(!1),
      [y, T] = (0, q.useState)(!1),
      [V, D] = (0, q.useState)(1),
      [B, U] = (0, q.useState)(""),
      [P, wa] = (0, q.useState)("All entries"),
      [Gs, sa] = (0, q.useState)(null),
      [Vs, Vi] = (0, q.useState)("details"),
      [H, j] = (0, q.useState)(null),
      [Xi, el] = (0, q.useState)(!1),
      [Yi, ed] = (0, q.useState)(!1),
      [Xs, zu] = (0, q.useState)(null),
      [ad, td] = (0, q.useState)(""),
      [ji, Zi] = (0, q.useState)(!1),
      [Qi, Ki] = (0, q.useState)(""),
      ld = (0, q.useRef)(null),
      Ys = (0, q.useRef)(null),
      Z = e.contacts.find((i) => i.id === Gs),
      Te = (0, q.useCallback)((i) => {
        (td(i),
          Ys.current && clearTimeout(Ys.current),
          (Ys.current = setTimeout(() => td(""), 5e3)));
      }, []);
    (0, q.useEffect)(() => {
      try {
        let i = window.localStorage.getItem("innercircle-theme");
        if (i === "light" || i === "dark") {
          document.documentElement.dataset.theme = i;
          let M = window.requestAnimationFrame(() => o(i));
          return () => window.cancelAnimationFrame(M);
        }
        document.documentElement.dataset.theme = "light";
      } catch {
        window.setTimeout(
          () =>
            Te(
              "Your appearance preference could not be loaded from this browser.",
            ),
          0,
        );
      }
    }, [Te]);
    function cg(i) {
      (o(i), (document.documentElement.dataset.theme = i));
      try {
        window.localStorage.setItem("innercircle-theme", i);
      } catch {
        Te(
          "Theme changed for this visit, but your browser could not save the preference.",
        );
      }
    }
    let al = (0, q.useCallback)(async () => {
      let i = await fetch(_i(), { cache: "no-store" }),
        M = await i.json();
      if (!i.ok) throw new Error(M.error || "Unable to load your workspace.");
      (a(M),
        l(!0),
        typeof window < "u" && window.__INSTALL__ && !M.installed && el(!0),
        M.authenticated || (sa(null), j(null), el(!1)));
    }, []);
    ((0, q.useEffect)(() => {
      al().catch((i) => {
        (Te(i.message), l(!0));
      });
    }, [al, Te]),
      (0, q.useEffect)(() => {
        let i = () => {
          (a((M) => ({
            ...M,
            authenticated: !1,
            contacts: [],
            accounts: [],
            journals: [],
          })),
            sa(null),
            j(null));
        };
        return (
          window.addEventListener("workspace-locked", i),
          () => window.removeEventListener("workspace-locked", i)
        );
      }, []),
      (0, q.useEffect)(() => {
        function i(M) {
          ((M.ctrlKey || M.metaKey) &&
            M.key === "k" &&
            (M.preventDefault(), ld.current?.focus()),
            M.key === "Escape" && (T(!1), zu(null), ed(!1)));
        }
        return (
          document.addEventListener("keydown", i),
          () => document.removeEventListener("keydown", i)
        );
      }, []),
      (0, q.useEffect)(() => {
        D(1);
      }, [h, n, k, A, r, d]),
      (0, q.useEffect)(() => {
        if (!e.installed || !e.authenticated) return;
        let i,
          M = () => {
            De("logout")
              .catch(() => {})
              .finally(() => {
                (a((ll) => ({
                  ...ll,
                  authenticated: !1,
                  contacts: [],
                  accounts: [],
                  journals: [],
                })),
                  j(null),
                  sa(null));
              });
          },
          z = () => {
            (clearTimeout(i), (i = setTimeout(M, e.autoLockMinutes * 6e4)));
          },
          _a = ["pointerdown", "keydown", "scroll"];
        return (
          _a.forEach((ll) => window.addEventListener(ll, z, { passive: !0 })),
          z(),
          () => {
            (clearTimeout(i),
              _a.forEach((ll) => window.removeEventListener(ll, z)));
          }
        );
      }, [e.installed, e.authenticated, e.autoLockMinutes]));
    let js = e.contacts.filter((i) => i.favorite).length,
      Zs = (0, q.useMemo)(
        () =>
          e.contacts.filter((i) => i.dob).sort((i, M) => Ne(i.dob) - Ne(M.dob)),
        [e.contacts],
      ),
      ud = Zs.filter((i) => Ne(i.dob) <= 30),
      tl = (0, q.useMemo)(() => {
        let i = e.contacts.filter(
          (M) =>
            (n === "All contacts" || n === M.group) &&
            (!(A || d === "Favorites") || M.favorite) &&
            (!r || Ne(M.dob) <= 30) &&
            [M.name, M.email, M.phone, M.title].some((z) =>
              z.toLowerCase().includes(h.toLowerCase().trim()),
            ),
        );
        return (
          k === "name" &&
            (i = [...i].sort((M, z) => M.name.localeCompare(z.name))),
          k === "birthday" &&
            (i = [...i].sort((M, z) => Ne(M.dob) - Ne(z.dob))),
          i
        );
      }, [e.contacts, n, A, r, h, k, d]),
      Qs = Math.max(1, Math.ceil(tl.length / 6)),
      ct = Math.min(V, Qs),
      Ks = tl.slice((ct - 1) * 6, ct * 6),
      Ji = e.accounts.filter(
        (i) =>
          (!B || i.contactId === B) &&
          [
            i.title,
            i.email,
            i.username,
            e.contacts.find((M) => M.id === i.contactId)?.name || "",
          ].some((M) => M.toLowerCase().includes(h.toLowerCase())),
      ),
      Wi = e.journals
        .filter(
          (i) =>
            (!B || i.contactId === B) &&
            (P === "All entries" || i.type === P) &&
            [
              i.notes,
              i.mood,
              e.contacts.find((M) => M.id === i.contactId)?.name || "",
            ].some((M) => M.toLowerCase().includes(h.toLowerCase())),
        )
        .sort((i, M) => M.date.localeCompare(i.date));
    function Be(i, M = "All contacts") {
      (f(i), p(M), x(""), T(!1), c(!1), g(!1), ed(!1), U(""));
    }
    function Tl(i) {
      (sa(i.id), Vi("details"));
    }
    async function od(i) {
      (await al(), Te(i));
    }
    async function Js(i) {
      if (!Qi) {
        (Ki(i.id),
          a((M) => ({
            ...M,
            contacts: M.contacts.map((z) =>
              z.id === i.id ? { ...z, favorite: !z.favorite } : z,
            ),
          })));
        try {
          await De("toggleFavorite", { id: i.id, favorite: !i.favorite });
        } catch (M) {
          (await al(), Te(M.message));
        } finally {
          Ki("");
        }
      }
    }
    function Bl(i, M) {
      if ((sa(null), !e.installed)) {
        el(!0);
        return;
      }
      if (i === "journal" && !e.journalEnabled) {
        (Be("Settings"),
          Te("Enable your private journal in privacy settings."));
        return;
      }
      if (!e.contacts.length) {
        (j({ type: "contact" }),
          Te("Add a person first, then connect their private details."));
        return;
      }
      j({ type: i, contactId: M });
    }
    async function $i(i) {
      try {
        let M = await De("revealAccount", { id: i.id });
        (sa(null), j({ type: "account", account: M.account }));
      } catch (M) {
        Te(M.message);
      }
    }
    async function mg() {
      if (!e.installed) {
        el(!0);
        return;
      }
      try {
        (await De("logout"),
          a((i) => ({
            ...i,
            authenticated: !1,
            contacts: [],
            accounts: [],
            journals: [],
          })));
      } catch (i) {
        Te(i.message);
      }
    }
    function pg() {
      let i = (z) => {
          let _a = String(z ?? "");
          return (
            /^[\s]*[=+\-@]/.test(_a) && (_a = "'" + _a),
            `"${_a.replaceAll('"', '""')}"`
          );
        },
        M = [
          [
            "Name",
            "Relationship",
            "Date of birth",
            "Age",
            "Email",
            "Phone",
            "Address",
            "Notes",
          ],
          ...tl.map((z) => [
            z.name,
            z.group,
            z.dob,
            ka(z.dob) ?? "",
            z.email,
            z.phone,
            z.address,
            z.notes,
          ]),
        ];
      (Ps(
        "innercircle-contacts.csv",
        "\uFEFF" +
          M.map((z) => z.map(i).join(",")).join(`\r
`),
        "text/csv;charset=utf-8;",
      ),
        Te(
          `${tl.length} contacts exported. Private accounts and journals were not included.`,
        ));
    }
    async function hg() {
      if (H?.type === "delete") {
        Zi(!0);
        try {
          (await De(`delete${H.kind}`, { id: H.id }),
            await od(
              `${H.kind === "Journal" ? "Journal entry" : H.kind} deleted.`,
            ),
            j(null),
            sa(null));
        } catch (i) {
          Te(i.message);
        } finally {
          Zi(!1);
        }
      }
    }
    let Ws = yy[d],
      er = ["Contacts", "Favorites", "Overview"].includes(d);
    if (t && !e.authenticated && !Xi)
      return (0, s.jsxs)(s.Fragment, {
        children: [
          (0, s.jsx)(dg, { onUnlock: al }),
          ad &&
            (0, s.jsxs)("div", {
              className: "toast",
              role: "status",
              children: [
                ad,
                (0, s.jsx)("button", {
                  "aria-label": "Dismiss notification",
                  onClick: () => td(""),
                  children: (0, s.jsx)(Fa, { size: 15 }),
                }),
              ],
            }),
        ],
      });
    let ar = (i) =>
      i.map((M) =>
        (0, s.jsxs)(
          "button",
          {
            className: "journal-card",
            onClick: () => {
              (sa(null), j({ type: "journalView", entry: M }));
            },
            children: [
              (0, s.jsxs)("div", {
                className: "journal-card-top",
                children: [
                  (0, s.jsxs)("span", {
                    className: `entry-type ${M.type.toLowerCase()}`,
                    children: [
                      M.type === "Intimacy"
                        ? (0, s.jsx)(ua, { size: 13 })
                        : (0, s.jsx)(Zt, { size: 13 }),
                      " ",
                      M.type,
                    ],
                  }),
                  (0, s.jsxs)("span", {
                    children: [$t(M.date), ", ", M.date.slice(0, 4)],
                  }),
                ],
              }),
              (0, s.jsx)("h3", {
                children: e.contacts.find((z) => z.id === M.contactId)?.name,
              }),
              (0, s.jsx)("p", { children: M.notes }),
              (0, s.jsxs)("div", {
                className: "journal-card-bottom",
                children: [
                  (0, s.jsx)("span", { className: "mood-dot" }),
                  M.mood,
                  (0, s.jsxs)("span", {
                    children: [(0, s.jsx)(ne, { size: 12 }), " Private entry"],
                  }),
                ],
              }),
            ],
          },
          M.id,
        ),
      );
    return (0, s.jsxs)("div", {
      className: "app-shell",
      onClick: () => {
        Xs && zu(null);
      },
      children: [
        Yi &&
          (0, s.jsx)("div", {
            className: "mobile-scrim",
            onClick: () => ed(!1),
          }),
        (0, s.jsxs)("aside", {
          className: `sidebar ${Yi ? "mobile-open" : ""}`,
          children: [
            (0, s.jsx)("a", {
              className: "logo-link",
              href: "#contacts",
              onClick: (i) => {
                (i.preventDefault(), Be("Contacts"));
              },
              children: (0, s.jsx)(Jo, {}),
            }),
            (0, s.jsxs)("button", {
              className: "workspace-switcher",
              onClick: () => Be("Settings"),
              children: [
                (0, s.jsx)("span", {
                  className: "workspace-symbol",
                  children: (0, s.jsx)(Po, { size: 18 }),
                }),
                (0, s.jsxs)("span", {
                  children: [
                    (0, s.jsx)("small", { children: "PERSONAL WORKSPACE" }),
                    (0, s.jsx)("strong", { children: e.name }),
                  ],
                }),
                (0, s.jsx)(Au, { size: 13 }),
              ],
            }),
            (0, s.jsx)("div", {
              className: "nav-section-label",
              children: "YOUR WORKSPACE",
            }),
            (0, s.jsxs)("nav", {
              className: "main-nav",
              "aria-label": "Main navigation",
              children: [
                [
                  { name: "Overview", icon: _o },
                  { name: "Contacts", icon: Wt, count: e.contacts.length },
                  { name: "Favorites", icon: ua, count: js },
                ].map(({ name: i, icon: M, count: z }) =>
                  (0, s.jsxs)(
                    "button",
                    {
                      className: `nav-item ${d === i ? "active" : ""}`,
                      onClick: () => Be(i),
                      children: [
                        (0, s.jsx)(M, { size: 18 }),
                        (0, s.jsx)("span", { children: i }),
                        z !== void 0 && (0, s.jsx)("small", { children: z }),
                      ],
                    },
                    i,
                  ),
                ),
                (0, s.jsx)("div", { className: "nav-divider" }),
                [
                  { name: "Accounts", icon: ne },
                  { name: "Private journal", icon: _t },
                ].map(({ name: i, icon: M }) =>
                  (0, s.jsxs)(
                    "button",
                    {
                      className: `nav-item ${d === i ? "active" : ""}`,
                      onClick: () => Be(i),
                      children: [
                        (0, s.jsx)(M, { size: 18 }),
                        (0, s.jsx)("span", { children: i }),
                        i === "Private journal" &&
                          (0, s.jsx)("span", { className: "private-nav-dot" }),
                      ],
                    },
                    i,
                  ),
                ),
              ],
            }),
            (0, s.jsxs)("div", {
              className: "nav-section-label circles-label",
              children: [
                "RELATIONSHIPS ",
                (0, s.jsx)("span", {
                  children: new Set(e.contacts.map((i) => i.group)).size,
                }),
              ],
            }),
            (0, s.jsx)("nav", {
              className: "circle-nav",
              "aria-label": "Relationships",
              children: [...new Set(e.contacts.map((i) => i.group))]
                .sort()
                .map((i) =>
                  (0, s.jsxs)(
                    "button",
                    {
                      className: `circle-item ${d === "Contacts" && n === i ? "chosen" : ""}`,
                      onClick: () => Be("Contacts", i),
                      children: [
                        (0, s.jsx)("i", {
                          className: `circle-dot ${i.toLowerCase().replaceAll(" ", "-")}`,
                        }),
                        (0, s.jsx)("span", { children: i }),
                        (0, s.jsx)("small", {
                          children: e.contacts.filter((M) => M.group === i)
                            .length,
                        }),
                      ],
                    },
                    i,
                  ),
                ),
            }),
            (0, s.jsxs)("div", {
              className: "sidebar-bottom",
              children: [
                (0, s.jsxs)("button", {
                  className: `nav-item ${d === "Settings" ? "active" : ""}`,
                  onClick: () => Be("Settings"),
                  children: [
                    (0, s.jsx)(wl, { size: 18 }),
                    (0, s.jsx)("span", { children: "Settings" }),
                  ],
                }),
                (0, s.jsxs)("button", {
                  className: "nav-item help-nav",
                  onClick: () => j({ type: "help" }),
                  children: [
                    (0, s.jsx)(ba, { size: 18 }),
                    (0, s.jsx)("span", { children: "A little guidance" }),
                    (0, s.jsx)(Na, { size: 14 }),
                  ],
                }),
                (0, s.jsxs)("div", {
                  className: "sidebar-privacy",
                  children: [
                    (0, s.jsx)("span", {
                      children: (0, s.jsx)(Xe, { size: 19 }),
                    }),
                    (0, s.jsx)("strong", { children: "Private by nature." }),
                    (0, s.jsxs)("p", {
                      children: [
                        "Your life isn\u2019t public.",
                        (0, s.jsx)("br", {}),
                        "Neither is your inner circle.",
                      ],
                    }),
                    (0, s.jsxs)("button", {
                      onClick: () => (e.installed ? Be("Settings") : el(!0)),
                      children: [
                        e.installed
                          ? "Your privacy settings"
                          : "Set up your workspace",
                        (0, s.jsx)(Ca, { size: 13 }),
                      ],
                    }),
                  ],
                }),
                (0, s.jsxs)("button", {
                  className: "sidebar-profile",
                  onClick: () => Be("Settings"),
                  children: [
                    (0, s.jsx)("span", {
                      className: "profile-avatar",
                      children: Ko(e.ownerName || "Your space"),
                    }),
                    (0, s.jsxs)("span", {
                      children: [
                        (0, s.jsx)("strong", {
                          children: e.ownerName || "Your workspace",
                        }),
                        (0, s.jsx)("small", {
                          children: e.installed
                            ? "Personal account"
                            : "Demo workspace",
                        }),
                      ],
                    }),
                    (0, s.jsx)(Au, { size: 14 }),
                  ],
                }),
              ],
            }),
          ],
        }),
        (0, s.jsxs)("div", {
          className: "main-shell",
          children: [
            (0, s.jsxs)("header", {
              className: "topbar",
              children: [
                (0, s.jsxs)("div", {
                  className: "breadcrumbs",
                  children: [
                    (0, s.jsx)("button", {
                      className: "icon-button mobile-menu",
                      "aria-label": "Open menu",
                      onClick: () => ed(!0),
                      children: (0, s.jsx)(Yo, { size: 20 }),
                    }),
                    (0, s.jsx)("button", {
                      className: "breadcrumb-home",
                      "aria-label": "Go to overview",
                      onClick: () => Be("Overview"),
                      children: (0, s.jsx)(jt, { size: 15 }),
                    }),
                    (0, s.jsx)("span", {
                      className: "breadcrumb-slash",
                      children: "/",
                    }),
                    (0, s.jsx)("span", {
                      className: "breadcrumb-workspace",
                      children: "My workspace",
                    }),
                    (0, s.jsx)(ku, { size: 12 }),
                    (0, s.jsx)("strong", { children: d }),
                  ],
                }),
                (0, s.jsxs)("div", {
                  className: "topbar-actions",
                  children: [
                    (0, s.jsxs)("button", {
                      className: "privacy-status",
                      onClick: () => Be("Settings"),
                      children: [
                        (0, s.jsx)("span", { className: "online-dot" }),
                        (0, s.jsx)(ne, { size: 12 }),
                        e.installed
                          ? "Private & protected"
                          : "Your personal space",
                      ],
                    }),
                    (0, s.jsx)("span", { className: "topbar-divider" }),
                    (0, s.jsxs)("button", {
                      className: "icon-button notification-button",
                      "aria-label": "Upcoming birthdays",
                      onClick: () => j({ type: "birthdays" }),
                      children: [
                        (0, s.jsx)(Uo, { size: 18 }),
                        ud.length > 0 && (0, s.jsx)("i", {}),
                      ],
                    }),
                    (0, s.jsx)("button", {
                      className: "topbar-avatar",
                      "aria-label": "Open profile settings",
                      onClick: () => Be("Settings"),
                      children: Ko(e.ownerName || "Your space"),
                    }),
                  ],
                }),
              ],
            }),
            (0, s.jsxs)("main", {
              className: "main-content",
              children: [
                (0, s.jsxs)("section", {
                  className: "page-heading",
                  children: [
                    (0, s.jsxs)("div", {
                      children: [
                        (0, s.jsx)("p", {
                          className: "eyebrow",
                          children: Ws.eyebrow,
                        }),
                        (0, s.jsx)("h1", { children: Ws.title }),
                        (0, s.jsx)("p", {
                          className: "page-description",
                          children: Ws.description,
                        }),
                      ],
                    }),
                    er
                      ? (0, s.jsxs)("button", {
                          className: "button primary add-contact",
                          onClick: () => j({ type: "contact" }),
                          children: [
                            (0, s.jsx)(Aa, { size: 17 }),
                            " Add contact",
                          ],
                        })
                      : d === "Accounts"
                        ? (0, s.jsxs)("button", {
                            className: "button primary",
                            onClick: () => Bl("account"),
                            children: [
                              (0, s.jsx)(Aa, { size: 17 }),
                              " Add account",
                            ],
                          })
                        : d === "Private journal"
                          ? (0, s.jsxs)("button", {
                              className: "button primary",
                              onClick: () => Bl("journal"),
                              children: [
                                (0, s.jsx)(Aa, { size: 17 }),
                                " New entry",
                              ],
                            })
                          : (0, s.jsxs)("button", {
                              className: "button secondary",
                              onClick: mg,
                              children: [
                                (0, s.jsx)(ne, { size: 15 }),
                                e.installed
                                  ? "Lock workspace"
                                  : "Set up workspace",
                              ],
                            }),
                  ],
                }),
                d !== "Settings" &&
                  (0, s.jsx)("section", {
                    className: "stats-row",
                    "aria-label": "Workspace at a glance",
                    children: [
                      {
                        label: "Total contacts",
                        value: e.contacts.length,
                        icon: Wt,
                        color: "green",
                        target: "Contacts",
                        note: "Your growing circle",
                      },
                      {
                        label: "Favorites",
                        value: js,
                        icon: ua,
                        color: "pink",
                        target: "Favorites",
                        note: "A little closer to you",
                      },
                      {
                        label: "Saved accounts",
                        value: e.accounts.length,
                        icon: ne,
                        color: "sand",
                        target: "Accounts",
                        note: "Details, kept together",
                      },
                      {
                        label: "Journal entries",
                        value: e.journals.length,
                        icon: _t,
                        color: "lilac",
                        target: "Private journal",
                        note: "Moments to remember",
                      },
                    ].map(
                      ({
                        label: i,
                        value: M,
                        icon: z,
                        color: _a,
                        target: ll,
                        note: Lg,
                      }) =>
                        (0, s.jsxs)(
                          "button",
                          {
                            className: "stat",
                            onClick: () => Be(ll),
                            children: [
                              (0, s.jsx)("span", {
                                className: `stat-icon ${_a}`,
                                children: (0, s.jsx)(z, {
                                  size: 20,
                                  strokeWidth: 1.6,
                                }),
                              }),
                              (0, s.jsxs)("div", {
                                children: [
                                  (0, s.jsx)("span", {
                                    className: "stat-label",
                                    children: i,
                                  }),
                                  (0, s.jsx)("strong", {
                                    children: String(M).padStart(2, "0"),
                                  }),
                                  (0, s.jsx)("span", {
                                    className: "stat-note",
                                    children: Lg,
                                  }),
                                ],
                              }),
                              (0, s.jsx)(Na, {
                                className: "stat-arrow",
                                size: 13,
                              }),
                            ],
                          },
                          i,
                        ),
                    ),
                  }),
                er &&
                  (0, s.jsxs)("div", {
                    className: "directory-layout",
                    children: [
                      (0, s.jsxs)("section", {
                        className: "directory",
                        children: [
                          (0, s.jsxs)("div", {
                            className: "directory-toolbar",
                            children: [
                              (0, s.jsxs)("div", {
                                className: "search-input",
                                children: [
                                  (0, s.jsx)(Ml, { size: 17 }),
                                  (0, s.jsx)("input", {
                                    ref: ld,
                                    "aria-label": "Search contacts",
                                    placeholder:
                                      "Search by name, email, or phone\u2026",
                                    value: h,
                                    onChange: (i) => x(i.target.value),
                                  }),
                                  h
                                    ? (0, s.jsx)("button", {
                                        className: "icon-button",
                                        "aria-label": "Clear search",
                                        onClick: () => x(""),
                                        children: (0, s.jsx)(Fa, { size: 14 }),
                                      })
                                    : (0, s.jsx)("kbd", {
                                        children: "\u2318 K",
                                      }),
                                ],
                              }),
                              (0, s.jsxs)("div", {
                                className: "filter-container",
                                children: [
                                  (0, s.jsxs)("button", {
                                    className: `button filter-button ${y || A || r ? "selected" : ""}`,
                                    "aria-expanded": y,
                                    onClick: () => T(!y),
                                    children: [
                                      (0, s.jsx)(Qo, { size: 15 }),
                                      (0, s.jsx)("span", {
                                        children: "Filters",
                                      }),
                                      (A || r) &&
                                        (0, s.jsx)("i", {
                                          className: "filter-dot",
                                        }),
                                    ],
                                  }),
                                  y &&
                                    (0, s.jsxs)("div", {
                                      className: "filter-popover",
                                      children: [
                                        (0, s.jsxs)("div", {
                                          className: "popover-heading",
                                          children: [
                                            (0, s.jsx)("strong", {
                                              children:
                                                "A little more specific",
                                            }),
                                            (0, s.jsx)("button", {
                                              className: "icon-button",
                                              "aria-label": "Close filters",
                                              onClick: () => T(!1),
                                              children: (0, s.jsx)(Fa, {
                                                size: 14,
                                              }),
                                            }),
                                          ],
                                        }),
                                        (0, s.jsxs)("label", {
                                          children: [
                                            "Sort contacts",
                                            (0, s.jsxs)("select", {
                                              value: k,
                                              onChange: (i) =>
                                                w(i.target.value),
                                              children: [
                                                (0, s.jsx)("option", {
                                                  value: "recent",
                                                  children: "Date added",
                                                }),
                                                (0, s.jsx)("option", {
                                                  value: "name",
                                                  children: "Name (A\u2013Z)",
                                                }),
                                                (0, s.jsx)("option", {
                                                  value: "birthday",
                                                  children: "Next birthday",
                                                }),
                                              ],
                                            }),
                                          ],
                                        }),
                                        (0, s.jsxs)("label", {
                                          className: "check-label",
                                          children: [
                                            (0, s.jsx)("input", {
                                              type: "checkbox",
                                              checked: A,
                                              onChange: (i) =>
                                                c(i.target.checked),
                                            }),
                                            " Favorites only",
                                          ],
                                        }),
                                        (0, s.jsxs)("label", {
                                          className: "check-label",
                                          children: [
                                            (0, s.jsx)("input", {
                                              type: "checkbox",
                                              checked: r,
                                              onChange: (i) =>
                                                g(i.target.checked),
                                            }),
                                            " Birthdays in the next 30 days",
                                          ],
                                        }),
                                        (0, s.jsx)("button", {
                                          className: "text-button",
                                          onClick: () => {
                                            (w("recent"), c(!1), g(!1));
                                          },
                                          children: "Reset filters",
                                        }),
                                      ],
                                    }),
                                ],
                              }),
                              (0, s.jsxs)("div", {
                                className: "view-toggle",
                                "aria-label": "Contact layout",
                                children: [
                                  (0, s.jsx)("button", {
                                    className: m === "grid" ? "active" : "",
                                    "aria-label": "Grid view",
                                    "aria-pressed": m === "grid",
                                    onClick: () => L("grid"),
                                    children: (0, s.jsx)(Go, { size: 16 }),
                                  }),
                                  (0, s.jsx)("button", {
                                    className: m === "list" ? "active" : "",
                                    "aria-label": "List view",
                                    "aria-pressed": m === "list",
                                    onClick: () => L("list"),
                                    children: (0, s.jsx)(Vo, { size: 18 }),
                                  }),
                                ],
                              }),
                            ],
                          }),
                          (0, s.jsxs)("div", {
                            className: "directory-tabs",
                            children: [
                              (0, s.jsx)("div", {
                                className: "circle-tabs",
                                children: [
                                  "All contacts",
                                  "Friends",
                                  "Family",
                                  "Work",
                                  ...(e.contacts.some(
                                    (i) => i.group === "Other",
                                  )
                                    ? ["Other"]
                                    : []),
                                ].map((i) =>
                                  (0, s.jsxs)(
                                    "button",
                                    {
                                      className: n === i ? "active" : "",
                                      onClick: () => p(i),
                                      children: [
                                        i,
                                        i === "All contacts" &&
                                          (0, s.jsx)("span", {
                                            children:
                                              d === "Favorites"
                                                ? js
                                                : e.contacts.length,
                                          }),
                                      ],
                                    },
                                    i,
                                  ),
                                ),
                              }),
                              (0, s.jsxs)("button", {
                                className: "export-button",
                                onClick: pg,
                                title: "Export filtered contacts as CSV",
                                children: [
                                  (0, s.jsx)(Vt, { size: 13 }),
                                  (0, s.jsx)("span", { children: "Export" }),
                                ],
                              }),
                            ],
                          }),
                          Ks.length
                            ? m === "grid"
                              ? (0, s.jsx)("div", {
                                  className: "contact-grid",
                                  children: Ks.map((i, M) =>
                                    (0, s.jsxs)(
                                      "article",
                                      {
                                        className: "contact-card",
                                        style: {
                                          animationDelay: `${M * 35}ms`,
                                        },
                                        onClick: () => Tl(i),
                                        onKeyDown: (z) => {
                                          z.key === "Enter" &&
                                            z.target === z.currentTarget &&
                                            Tl(i);
                                        },
                                        tabIndex: 0,
                                        "aria-label": `View ${i.name}`,
                                        children: [
                                          (0, s.jsxs)("div", {
                                            className: "contact-card-body",
                                            children: [
                                              (0, s.jsxs)("div", {
                                                className: "contact-top",
                                                children: [
                                                  (0, s.jsx)(it, {
                                                    person: i,
                                                    size: "md",
                                                  }),
                                                  (0, s.jsxs)("div", {
                                                    className: "card-top-right",
                                                    children: [
                                                      (0, s.jsx)(Fs, {
                                                        group: i.group,
                                                      }),
                                                      (0, s.jsxs)("div", {
                                                        className:
                                                          "card-actions",
                                                        children: [
                                                          (0, s.jsx)("button", {
                                                            className: `favorite-button ${i.favorite ? "is-favorite" : ""}`,
                                                            "aria-label": `${i.favorite ? "Remove" : "Add"} ${i.name} ${i.favorite ? "from" : "to"} favorites`,
                                                            disabled:
                                                              Qi === i.id,
                                                            onClick: (z) => {
                                                              (z.stopPropagation(),
                                                                Js(i));
                                                            },
                                                            children: (0,
                                                            s.jsx)(Jt, {
                                                              size: 15,
                                                              fill: i.favorite
                                                                ? "currentColor"
                                                                : "none",
                                                              strokeWidth: 1.6,
                                                            }),
                                                          }),
                                                          (0, s.jsxs)("div", {
                                                            className:
                                                              "contact-menu-container",
                                                            children: [
                                                              (0, s.jsx)(
                                                                "button",
                                                                {
                                                                  className:
                                                                    "icon-button card-more",
                                                                  "aria-label": `More options for ${i.name}`,
                                                                  onClick: (
                                                                    z,
                                                                  ) => {
                                                                    (z.stopPropagation(),
                                                                      zu(
                                                                        Xs ===
                                                                          i.id
                                                                          ? null
                                                                          : i.id,
                                                                      ));
                                                                  },
                                                                  children: (0,
                                                                  s.jsx)(Xt, {
                                                                    size: 18,
                                                                  }),
                                                                },
                                                              ),
                                                              Xs === i.id &&
                                                                (0, s.jsxs)(
                                                                  "div",
                                                                  {
                                                                    className:
                                                                      "contact-menu",
                                                                    onClick: (
                                                                      z,
                                                                    ) =>
                                                                      z.stopPropagation(),
                                                                    children: [
                                                                      (0,
                                                                      s.jsxs)(
                                                                        "button",
                                                                        {
                                                                          onClick:
                                                                            () => {
                                                                              (j(
                                                                                {
                                                                                  type: "contact",
                                                                                  contact:
                                                                                    i,
                                                                                },
                                                                              ),
                                                                                zu(
                                                                                  null,
                                                                                ));
                                                                            },
                                                                          children:
                                                                            [
                                                                              (0,
                                                                              s.jsx)(
                                                                                Qt,
                                                                                {
                                                                                  size: 13,
                                                                                },
                                                                              ),
                                                                              "Edit contact",
                                                                            ],
                                                                        },
                                                                      ),
                                                                      (0,
                                                                      s.jsxs)(
                                                                        "button",
                                                                        {
                                                                          className:
                                                                            "danger-text",
                                                                          onClick:
                                                                            () => {
                                                                              (j(
                                                                                {
                                                                                  type: "delete",
                                                                                  kind: "Contact",
                                                                                  id: i.id,
                                                                                  name: i.name,
                                                                                },
                                                                              ),
                                                                                zu(
                                                                                  null,
                                                                                ));
                                                                            },
                                                                          children:
                                                                            [
                                                                              (0,
                                                                              s.jsx)(
                                                                                oa,
                                                                                {
                                                                                  size: 13,
                                                                                },
                                                                              ),
                                                                              "Delete contact",
                                                                            ],
                                                                        },
                                                                      ),
                                                                    ],
                                                                  },
                                                                ),
                                                            ],
                                                          }),
                                                        ],
                                                      }),
                                                    ],
                                                  }),
                                                ],
                                              }),
                                              (0, s.jsx)("h3", {
                                                children: i.name,
                                              }),
                                              (0, s.jsx)("p", {
                                                className:
                                                  "contact-description",
                                                title: i.title,
                                                children:
                                                  i.title ||
                                                  "A new face in your circle",
                                              }),
                                              (0, s.jsxs)("div", {
                                                className: "contact-links",
                                                children: [
                                                  (0, s.jsxs)("a", {
                                                    href: i.email
                                                      ? `mailto:${i.email}`
                                                      : void 0,
                                                    onClick: (z) =>
                                                      z.stopPropagation(),
                                                    children: [
                                                      (0, s.jsx)(wu, {
                                                        size: 13,
                                                      }),
                                                      (0, s.jsx)("span", {
                                                        children:
                                                          i.email ||
                                                          "No email added",
                                                      }),
                                                    ],
                                                  }),
                                                  (0, s.jsxs)("a", {
                                                    href: i.phone
                                                      ? `tel:${i.phone.replace(/[^+\d]/g, "")}`
                                                      : void 0,
                                                    onClick: (z) =>
                                                      z.stopPropagation(),
                                                    children: [
                                                      (0, s.jsx)(Du, {
                                                        size: 13,
                                                      }),
                                                      (0, s.jsx)("span", {
                                                        children:
                                                          i.phone ||
                                                          "No phone added",
                                                      }),
                                                    ],
                                                  }),
                                                ],
                                              }),
                                            ],
                                          }),
                                          (0, s.jsxs)("div", {
                                            className: "contact-card-footer",
                                            children: [
                                              (0, s.jsxs)("span", {
                                                children: [
                                                  (0, s.jsx)(nt, { size: 13 }),
                                                  $t(i.dob),
                                                  i.dob &&
                                                    (0, s.jsx)("i", {
                                                      children: "\xB7",
                                                    }),
                                                  i.dob &&
                                                    `${ka(i.dob)} years old`,
                                                ],
                                              }),
                                              (0, s.jsx)(Na, { size: 14 }),
                                            ],
                                          }),
                                        ],
                                      },
                                      i.id,
                                    ),
                                  ),
                                })
                              : (0, s.jsx)("div", {
                                  className: "contact-table-wrapper",
                                  children: (0, s.jsxs)("table", {
                                    className: "contact-table",
                                    children: [
                                      (0, s.jsx)("thead", {
                                        children: (0, s.jsxs)("tr", {
                                          children: [
                                            (0, s.jsx)("th", {
                                              children: "PERSON",
                                            }),
                                            (0, s.jsx)("th", {
                                              children: "CONTACT DETAILS",
                                            }),
                                            (0, s.jsx)("th", {
                                              children: "CIRCLE",
                                            }),
                                            (0, s.jsx)("th", {
                                              children: "BIRTHDAY",
                                            }),
                                            (0, s.jsx)("th", {}),
                                          ],
                                        }),
                                      }),
                                      (0, s.jsx)("tbody", {
                                        children: Ks.map((i) =>
                                          (0, s.jsxs)(
                                            "tr",
                                            {
                                              onClick: () => Tl(i),
                                              children: [
                                                (0, s.jsx)("td", {
                                                  children: (0, s.jsxs)(
                                                    "button",
                                                    {
                                                      className: "table-person",
                                                      onClick: () => Tl(i),
                                                      children: [
                                                        (0, s.jsx)(it, {
                                                          person: i,
                                                          size: "sm",
                                                        }),
                                                        (0, s.jsxs)("span", {
                                                          children: [
                                                            (0, s.jsx)(
                                                              "strong",
                                                              {
                                                                children:
                                                                  i.name,
                                                              },
                                                            ),
                                                            (0, s.jsx)(
                                                              "small",
                                                              {
                                                                children:
                                                                  i.title,
                                                              },
                                                            ),
                                                          ],
                                                        }),
                                                      ],
                                                    },
                                                  ),
                                                }),
                                                (0, s.jsxs)("td", {
                                                  children: [
                                                    (0, s.jsx)("span", {
                                                      children:
                                                        i.email || "\u2014",
                                                    }),
                                                    (0, s.jsx)("small", {
                                                      children:
                                                        i.phone || "\u2014",
                                                    }),
                                                  ],
                                                }),
                                                (0, s.jsx)("td", {
                                                  children: (0, s.jsx)(Fs, {
                                                    group: i.group,
                                                  }),
                                                }),
                                                (0, s.jsxs)("td", {
                                                  children: [
                                                    $t(i.dob),
                                                    (0, s.jsx)("small", {
                                                      children: i.dob
                                                        ? `${ka(i.dob)} years old`
                                                        : "",
                                                    }),
                                                  ],
                                                }),
                                                (0, s.jsx)("td", {
                                                  children: (0, s.jsx)(
                                                    "button",
                                                    {
                                                      className: `favorite-button ${i.favorite ? "is-favorite" : ""}`,
                                                      "aria-label": `Favorite ${i.name}`,
                                                      onClick: (M) => {
                                                        (M.stopPropagation(),
                                                          Js(i));
                                                      },
                                                      children: (0, s.jsx)(Jt, {
                                                        size: 15,
                                                        fill: i.favorite
                                                          ? "currentColor"
                                                          : "none",
                                                      }),
                                                    },
                                                  ),
                                                }),
                                              ],
                                            },
                                            i.id,
                                          ),
                                        ),
                                      }),
                                    ],
                                  }),
                                })
                            : (0, s.jsx)(Wo, {
                                icon: (0, s.jsx)(Wt, { size: 34 }),
                                title:
                                  d === "Favorites"
                                    ? "Keep your favorites close."
                                    : h || n !== "All contacts" || r || A
                                      ? "No familiar faces here. Yet."
                                      : "Your circle starts with one person.",
                                description:
                                  h || n !== "All contacts" || r || A
                                    ? "Try another search or give those filters a little more room."
                                    : d === "Favorites"
                                      ? "Tap the star on a contact to give them a place here."
                                      : "Add a friend, family member, or someone who makes your day.",
                                action: (0, s.jsxs)("button", {
                                  className: "button secondary",
                                  onClick: () => {
                                    h || n !== "All contacts" || r || A
                                      ? (x(""), p("All contacts"), g(!1), c(!1))
                                      : d === "Favorites"
                                        ? Be("Contacts")
                                        : j({ type: "contact" });
                                  },
                                  children: [
                                    h || n !== "All contacts" || r || A
                                      ? "Clear search & filters"
                                      : d === "Favorites"
                                        ? "Find your people"
                                        : "Add your first contact",
                                    (0, s.jsx)(Ca, { size: 15 }),
                                  ],
                                }),
                              }),
                          (0, s.jsxs)("div", {
                            className: "directory-footer",
                            children: [
                              (0, s.jsx)("span", {
                                children: tl.length
                                  ? `Showing ${(ct - 1) * 6 + 1}\u2013${Math.min(ct * 6, tl.length)} of ${tl.length} contacts`
                                  : "A little space for your people.",
                              }),
                              (0, s.jsxs)("div", {
                                className: "pagination",
                                children: [
                                  (0, s.jsx)("button", {
                                    "aria-label": "Previous page",
                                    disabled: ct <= 1,
                                    onClick: () => D((i) => Math.max(1, i - 1)),
                                    children: (0, s.jsx)(Ho, { size: 15 }),
                                  }),
                                  Array.from(
                                    { length: Qs },
                                    (i, M) => M + 1,
                                  ).map((i) =>
                                    (0, s.jsx)(
                                      "button",
                                      {
                                        className: ct === i ? "active" : "",
                                        "aria-label": `Page ${i}`,
                                        "aria-current":
                                          ct === i ? "page" : void 0,
                                        onClick: () => D(i),
                                        children: i,
                                      },
                                      i,
                                    ),
                                  ),
                                  (0, s.jsx)("button", {
                                    "aria-label": "Next page",
                                    disabled: ct >= Qs,
                                    onClick: () => D((i) => i + 1),
                                    children: (0, s.jsx)(ku, { size: 15 }),
                                  }),
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                      (0, s.jsxs)("aside", {
                        className: "right-rail",
                        children: [
                          (0, s.jsxs)("section", {
                            className: "birthday-card",
                            children: [
                              (0, s.jsxs)("div", {
                                className: "rail-card-heading",
                                children: [
                                  (0, s.jsx)("span", {
                                    className: "birthday-icon",
                                    children: (0, s.jsx)(Mu, { size: 17 }),
                                  }),
                                  (0, s.jsx)("h2", {
                                    children: "Coming up soon",
                                  }),
                                  (0, s.jsx)("span", {
                                    className: "tiny-counter",
                                    children: ud.length,
                                  }),
                                ],
                              }),
                              (0, s.jsx)("p", {
                                className: "rail-description",
                                children:
                                  "A little reminder to make their day.",
                              }),
                              (0, s.jsxs)("div", {
                                className: "birthday-list",
                                children: [
                                  ud
                                    .slice(0, 3)
                                    .map((i) =>
                                      (0, s.jsxs)(
                                        "button",
                                        {
                                          className: "birthday-person",
                                          onClick: () => Tl(i),
                                          children: [
                                            (0, s.jsx)(it, {
                                              person: i,
                                              size: "sm",
                                            }),
                                            (0, s.jsxs)("span", {
                                              children: [
                                                (0, s.jsx)("strong", {
                                                  children: i.name,
                                                }),
                                                (0, s.jsxs)("small", {
                                                  children: [
                                                    $t(i.dob),
                                                    " ",
                                                    (0, s.jsx)("i", {
                                                      children: "\xB7",
                                                    }),
                                                    " Turning ",
                                                    (ka(i.dob) || 0) +
                                                      (Ne(i.dob) > 0 ? 1 : 0),
                                                  ],
                                                }),
                                              ],
                                            }),
                                            (0, s.jsx)("span", {
                                              className: `birthday-countdown ${Ne(i.dob) <= 3 ? "soon" : ""}`,
                                              children:
                                                Ne(i.dob) === 0
                                                  ? "Today!"
                                                  : `${Ne(i.dob)} days`,
                                            }),
                                          ],
                                        },
                                        i.id,
                                      ),
                                    ),
                                  !ud.length &&
                                    (0, s.jsxs)("div", {
                                      className: "no-birthdays",
                                      children: [
                                        (0, s.jsx)(nt, { size: 23 }),
                                        (0, s.jsxs)("p", {
                                          children: [
                                            "No birthdays in the next 30 days.",
                                            (0, s.jsx)("br", {}),
                                            "A thoughtful hello is always in season.",
                                          ],
                                        }),
                                      ],
                                    }),
                                ],
                              }),
                              (0, s.jsxs)("button", {
                                className: "rail-link",
                                onClick: () => j({ type: "birthdays" }),
                                children: [
                                  "See all birthdays ",
                                  (0, s.jsx)(Ca, { size: 14 }),
                                ],
                              }),
                            ],
                          }),
                          (0, s.jsxs)("section", {
                            className: "privacy-card",
                            children: [
                              (0, s.jsx)("div", {
                                className: "privacy-art",
                                children: (0, s.jsxs)("svg", {
                                  viewBox: "0 0 220 84",
                                  "aria-hidden": "true",
                                  fill: "none",
                                  children: [
                                    (0, s.jsx)("circle", {
                                      cx: "112",
                                      cy: "42",
                                      r: "37",
                                      stroke: "currentColor",
                                      opacity: ".18",
                                    }),
                                    (0, s.jsx)("circle", {
                                      cx: "112",
                                      cy: "42",
                                      r: "28",
                                      stroke: "currentColor",
                                      opacity: ".2",
                                      strokeDasharray: "2 5",
                                    }),
                                    (0, s.jsx)("path", {
                                      d: "M55 63c7-10 12-24 7-36m-1 21C42 46 42 35 44 30c14 1 18 7 17 18Zm1-10c14-3 18-12 15-21-11 4-17 10-15 21m102 25c-6-10-11-24-6-36m1 21c19-2 19-13 17-18-14 1-18 7-17 18Zm-1-10c-14-3-18-12-15-21 11 4 17 10 15 21",
                                      stroke: "currentColor",
                                      strokeWidth: "1.2",
                                      opacity: ".6",
                                    }),
                                    (0, s.jsx)("rect", {
                                      x: "101",
                                      y: "37",
                                      width: "22",
                                      height: "19",
                                      rx: "4",
                                      stroke: "currentColor",
                                      strokeWidth: "1.5",
                                    }),
                                    (0, s.jsx)("path", {
                                      d: "M106 37v-6a6 6 0 0 1 12 0v6m-6 7v5",
                                      stroke: "currentColor",
                                      strokeWidth: "1.5",
                                      strokeLinecap: "round",
                                    }),
                                    (0, s.jsx)("path", {
                                      d: "m31 52 2-4 2 4 4 2-4 2-2 4-2-4-4-2 4-2Zm158-29 1.5-3 1.5 3 3 1.5-3 1.5-1.5 3-1.5-3-3-1.5L189 23Z",
                                      fill: "currentColor",
                                      opacity: ".6",
                                    }),
                                  ],
                                }),
                              }),
                              (0, s.jsx)("p", {
                                className: "privacy-eyebrow",
                                children: "PERSONAL. NOT PUBLIC.",
                              }),
                              (0, s.jsxs)("h2", {
                                children: [
                                  "Your circle.",
                                  (0, s.jsx)("br", {}),
                                  "Just for your eyes.",
                                ],
                              }),
                              (0, s.jsx)("p", {
                                children:
                                  "Your people, private details, and personal moments deserve a space of their own.",
                              }),
                              (0, s.jsxs)("button", {
                                onClick: () => Be("Settings"),
                                children: [
                                  "A little peace of mind ",
                                  (0, s.jsx)(Na, { size: 14 }),
                                ],
                              }),
                            ],
                          }),
                          (0, s.jsxs)("div", {
                            className: "thoughtful-note",
                            children: [
                              (0, s.jsx)("span", {
                                children: (0, s.jsx)(kl, {
                                  size: 18,
                                  strokeWidth: 1.5,
                                }),
                              }),
                              (0, s.jsxs)("p", {
                                children: [
                                  "Good connections grow",
                                  (0, s.jsx)("br", {}),
                                  "with a little care.",
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                d === "Accounts" &&
                  (0, s.jsxs)("section", {
                    className: "vault-section",
                    children: [
                      (0, s.jsxs)("div", {
                        className: "section-toolbar",
                        children: [
                          (0, s.jsxs)("div", {
                            className: "search-input",
                            children: [
                              (0, s.jsx)(Ml, { size: 17 }),
                              (0, s.jsx)("input", {
                                ref: ld,
                                "aria-label": "Search accounts",
                                placeholder:
                                  "Find an account or a person\u2026",
                                value: h,
                                onChange: (i) => x(i.target.value),
                              }),
                            ],
                          }),
                          (0, s.jsxs)("select", {
                            "aria-label": "Filter accounts by contact",
                            value: B,
                            onChange: (i) => U(i.target.value),
                            children: [
                              (0, s.jsx)("option", {
                                value: "",
                                children: "Everyone in your circle",
                              }),
                              e.contacts.map((i) =>
                                (0, s.jsx)(
                                  "option",
                                  { value: i.id, children: i.name },
                                  i.id,
                                ),
                              ),
                            ],
                          }),
                        ],
                      }),
                      Ji.length
                        ? (0, s.jsx)("div", {
                            className: "vault-grid",
                            children: Ji.map((i) =>
                              (0, s.jsx)(
                                fg,
                                {
                                  account: i,
                                  person: e.contacts.find(
                                    (M) => M.id === i.contactId,
                                  ),
                                  notify: Te,
                                  onEdit: () => $i(i),
                                  onDelete: () =>
                                    j({
                                      type: "delete",
                                      kind: "Account",
                                      id: i.id,
                                      name: i.title,
                                    }),
                                },
                                i.id,
                              ),
                            ),
                          })
                        : (0, s.jsx)(Wo, {
                            icon: (0, s.jsx)(ne, {
                              size: 34,
                              strokeWidth: 1.4,
                            }),
                            title:
                              h || B
                                ? "No accounts found."
                                : "A safe place for the little details.",
                            description:
                              h || B
                                ? "Try a different person or search term."
                                : "Keep usernames, passwords, and banking details together, connected to the right person. Encrypted and just for you.",
                            action: (0, s.jsxs)("button", {
                              className: "button primary",
                              onClick: () => Bl("account"),
                              children: [
                                (0, s.jsx)(Aa, { size: 16 }),
                                "Add your first account",
                              ],
                            }),
                          }),
                      (0, s.jsx)(Dl, {
                        children:
                          "Only save account information you have permission to store. Secrets are encrypted at rest.",
                      }),
                    ],
                  }),
                d === "Private journal" &&
                  (0, s.jsxs)("section", {
                    className: "journal-section-main",
                    children: [
                      (0, s.jsxs)("div", {
                        className: "section-toolbar",
                        children: [
                          (0, s.jsxs)("div", {
                            className: "search-input",
                            children: [
                              (0, s.jsx)(Ml, { size: 17 }),
                              (0, s.jsx)("input", {
                                ref: ld,
                                "aria-label": "Search journal",
                                placeholder: "Find a moment or a person\u2026",
                                value: h,
                                onChange: (i) => x(i.target.value),
                              }),
                            ],
                          }),
                          (0, s.jsxs)("select", {
                            "aria-label": "Filter journal by contact",
                            value: B,
                            onChange: (i) => U(i.target.value),
                            children: [
                              (0, s.jsx)("option", {
                                value: "",
                                children: "Everyone in your circle",
                              }),
                              e.contacts.map((i) =>
                                (0, s.jsx)(
                                  "option",
                                  { value: i.id, children: i.name },
                                  i.id,
                                ),
                              ),
                            ],
                          }),
                        ],
                      }),
                      (0, s.jsxs)("div", {
                        className: "directory-tabs",
                        children: [
                          (0, s.jsx)("div", {
                            className: "circle-tabs",
                            children: [
                              "All entries",
                              "Reflection",
                              "Intimacy",
                            ].map((i) =>
                              (0, s.jsx)(
                                "button",
                                {
                                  className: i === P ? "active" : "",
                                  onClick: () => wa(i),
                                  children: i,
                                },
                                i,
                              ),
                            ),
                          }),
                          (0, s.jsxs)("span", {
                            className: "journal-private-label",
                            children: [
                              (0, s.jsx)(ne, { size: 12 }),
                              " Always private",
                            ],
                          }),
                        ],
                      }),
                      e.journalEnabled
                        ? Wi.length
                          ? (0, s.jsx)("div", {
                              className: "journal-grid",
                              children: ar(Wi),
                            })
                          : (0, s.jsx)(Wo, {
                              icon: (0, s.jsx)(Zt, {
                                size: 34,
                                strokeWidth: 1.3,
                              }),
                              title:
                                h || B || P !== "All entries"
                                  ? "No entries in this little corner."
                                  : "Some moments deserve a little space.",
                              description:
                                h || B || P !== "All entries"
                                  ? "Try another search, person, or entry type."
                                  : "Remember a conversation. Reflect on a connection. Check in after intimacy with thoughtful, consent-focused questions. This is your space.",
                              action: (0, s.jsxs)("button", {
                                className: "button primary",
                                onClick: () => Bl("journal"),
                                children: [
                                  (0, s.jsx)(Aa, { size: 16 }),
                                  " Write your first entry",
                                ],
                              }),
                            })
                        : (0, s.jsx)(Wo, {
                            icon: (0, s.jsx)(_t, { size: 34 }),
                            title: "Here, whenever you\u2019re ready.",
                            description:
                              "Your private journal is turned off. Enable it in Settings to create reflections and adult intimacy check-ins.",
                            action: (0, s.jsxs)("button", {
                              className: "button primary",
                              onClick: () => Be("Settings"),
                              children: [
                                "Privacy settings ",
                                (0, s.jsx)(Ca, { size: 15 }),
                              ],
                            }),
                          }),
                      (0, s.jsx)(Dl, {
                        children:
                          "Intimacy entries are for adults 18+ only. Your journal is never included in contact exports.",
                      }),
                    ],
                  }),
                d === "Settings" &&
                  (0, s.jsx)(
                    sg,
                    {
                      data: e,
                      theme: u,
                      onThemeChange: cg,
                      onDone: al,
                      onSetup: () => el(!0),
                      notify: Te,
                    },
                    `${e.installed}-${e.name}`,
                  ),
                (0, s.jsxs)("footer", {
                  className: "page-footer",
                  children: [
                    (0, s.jsxs)("span", {
                      children: [
                        (0, s.jsx)(Jo, { small: !0 }),
                        " A little closer to the people who matter.",
                      ],
                    }),
                    (0, s.jsx)("span", {
                      children: "Made for your inner circle.",
                    }),
                  ],
                }),
              ],
            }),
          ],
        }),
        Z &&
          (0, s.jsxs)(Ma, {
            title: Z.name,
            subtitle: "The little details, all together.",
            onClose: () => sa(null),
            wide: !0,
            children: [
              (0, s.jsxs)("div", {
                className: "detail-hero",
                children: [
                  (0, s.jsx)(it, { person: Z, size: "lg" }),
                  (0, s.jsxs)("div", {
                    children: [
                      (0, s.jsx)(Fs, { group: Z.group }),
                      (0, s.jsx)("p", {
                        children: Z.title || "Someone in your inner circle",
                      }),
                    ],
                  }),
                  (0, s.jsx)("button", {
                    className: `favorite-button ${Z.favorite ? "is-favorite" : ""}`,
                    "aria-label": "Toggle favorite",
                    onClick: () => Js(Z),
                    children: (0, s.jsx)(Jt, {
                      size: 21,
                      fill: Z.favorite ? "currentColor" : "none",
                    }),
                  }),
                ],
              }),
              (0, s.jsx)("div", {
                className: "detail-tabs",
                children: [
                  { id: "details", label: "Contact details", count: void 0 },
                  {
                    id: "accounts",
                    label: "Accounts",
                    count: e.accounts.filter((i) => i.contactId === Z.id)
                      .length,
                  },
                  {
                    id: "journal",
                    label: "Private journal",
                    count: e.journals.filter((i) => i.contactId === Z.id)
                      .length,
                  },
                ].map((i) =>
                  (0, s.jsxs)(
                    "button",
                    {
                      className: Vs === i.id ? "active" : "",
                      onClick: () => Vi(i.id),
                      children: [
                        i.label,
                        i.count !== void 0 &&
                          (0, s.jsx)("span", { children: i.count }),
                      ],
                    },
                    i.id,
                  ),
                ),
              }),
              (0, s.jsx)("div", {
                className: "detail-content",
                children:
                  Vs === "details"
                    ? (0, s.jsxs)(s.Fragment, {
                        children: [
                          (0, s.jsxs)("div", {
                            className: "detail-info-grid",
                            children: [
                              (0, s.jsx)(_s, {
                                icon: (0, s.jsx)(wu, { size: 17 }),
                                label: "Email address",
                                value: Z.email,
                                href: Z.email ? `mailto:${Z.email}` : void 0,
                              }),
                              (0, s.jsx)(_s, {
                                icon: (0, s.jsx)(Du, { size: 17 }),
                                label: "Phone number",
                                value: Z.phone,
                                href: Z.phone
                                  ? `tel:${Z.phone.replace(/[^+\d]/g, "")}`
                                  : void 0,
                              }),
                              (0, s.jsx)(_s, {
                                icon: (0, s.jsx)(nt, { size: 17 }),
                                label: "Date of birth",
                                value: Z.dob
                                  ? `${$t(Z.dob)}, ${Z.dob.slice(0, 4)} \xB7 ${ka(Z.dob)} years old`
                                  : "",
                              }),
                              (0, s.jsx)(_s, {
                                icon: (0, s.jsx)(Xo, { size: 17 }),
                                label: "Address \xB7 optional",
                                value: Z.address,
                              }),
                            ],
                          }),
                          (0, s.jsxs)("div", {
                            className: "detail-notes",
                            children: [
                              (0, s.jsx)("span", {
                                className: "little-label",
                                children: "A FEW THINGS TO REMEMBER",
                              }),
                              (0, s.jsx)("p", {
                                children:
                                  Z.notes ||
                                  "Their coffee order, your first meeting, a favorite memory. Add a little note to make this contact feel more personal.",
                              }),
                            ],
                          }),
                          (0, s.jsxs)("div", {
                            className: "form-footer",
                            children: [
                              (0, s.jsxs)("button", {
                                className: "text-button danger-text",
                                onClick: () => {
                                  (sa(null),
                                    j({
                                      type: "delete",
                                      kind: "Contact",
                                      id: Z.id,
                                      name: Z.name,
                                    }));
                                },
                                children: [
                                  (0, s.jsx)(oa, { size: 14 }),
                                  "Delete contact",
                                ],
                              }),
                              (0, s.jsxs)("button", {
                                className: "button primary",
                                onClick: () => {
                                  (j({ type: "contact", contact: Z }),
                                    sa(null));
                                },
                                children: [
                                  (0, s.jsx)(Qt, { size: 15 }),
                                  "Edit contact",
                                ],
                              }),
                            ],
                          }),
                        ],
                      })
                    : Vs === "accounts"
                      ? (0, s.jsxs)(s.Fragment, {
                          children: [
                            (0, s.jsxs)("div", {
                              className: "detail-section-heading",
                              children: [
                                (0, s.jsx)("h3", {
                                  children: "Their saved accounts",
                                }),
                                (0, s.jsxs)("button", {
                                  className: "button secondary small-button",
                                  onClick: () => Bl("account", Z.id),
                                  children: [
                                    (0, s.jsx)(Aa, { size: 14 }),
                                    "Add account",
                                  ],
                                }),
                              ],
                            }),
                            e.accounts.filter((i) => i.contactId === Z.id)
                              .length
                              ? (0, s.jsx)("div", {
                                  className: "detail-accounts",
                                  children: e.accounts
                                    .filter((i) => i.contactId === Z.id)
                                    .map((i) =>
                                      (0, s.jsx)(
                                        fg,
                                        {
                                          account: i,
                                          person: Z,
                                          notify: Te,
                                          onEdit: () => $i(i),
                                          onDelete: () => {
                                            (sa(null),
                                              j({
                                                type: "delete",
                                                kind: "Account",
                                                id: i.id,
                                                name: i.title,
                                              }));
                                          },
                                        },
                                        i.id,
                                      ),
                                    ),
                                })
                              : (0, s.jsxs)("div", {
                                  className: "detail-empty",
                                  children: [
                                    (0, s.jsx)(ne, { size: 25 }),
                                    (0, s.jsx)("h3", {
                                      children: "No accounts tucked away yet.",
                                    }),
                                    (0, s.jsxs)("p", {
                                      children: [
                                        "Connect an online or bank account to ",
                                        Z.name.split(" ")[0],
                                        ".",
                                      ],
                                    }),
                                  ],
                                }),
                          ],
                        })
                      : (0, s.jsxs)(s.Fragment, {
                          children: [
                            (0, s.jsxs)("div", {
                              className: "detail-section-heading",
                              children: [
                                (0, s.jsxs)("h3", {
                                  children: [
                                    "Moments with ",
                                    Z.name.split(" ")[0],
                                  ],
                                }),
                                (0, s.jsxs)("button", {
                                  className: "button secondary small-button",
                                  onClick: () => Bl("journal", Z.id),
                                  children: [
                                    (0, s.jsx)(Aa, { size: 14 }),
                                    "New entry",
                                  ],
                                }),
                              ],
                            }),
                            e.journals.filter((i) => i.contactId === Z.id)
                              .length
                              ? (0, s.jsx)("div", {
                                  className: "detail-journals",
                                  children: ar(
                                    e.journals.filter(
                                      (i) => i.contactId === Z.id,
                                    ),
                                  ),
                                })
                              : (0, s.jsxs)("div", {
                                  className: "detail-empty",
                                  children: [
                                    (0, s.jsx)(_t, { size: 27 }),
                                    (0, s.jsx)("h3", {
                                      children: "A story still being written.",
                                    }),
                                    (0, s.jsx)("p", {
                                      children:
                                        "Add a reflection or a private, adult-only intimacy check-in.",
                                    }),
                                  ],
                                }),
                          ],
                        }),
              }),
            ],
          }),
        H?.type === "contact" &&
          (0, s.jsx)(Ma, {
            title: H.contact
              ? "A little update."
              : "Someone worth remembering.",
            subtitle: H.contact
              ? "Good connections grow with a little care."
              : "Bring a familiar face into your inner circle.",
            onClose: () => j(null),
            wide: !0,
            children: (0, s.jsx)(tg, {
              contact: H.contact,
              onDone: () =>
                od(
                  H.contact
                    ? "Contact updated. The little details matter."
                    : "A new connection, added to your circle.",
                ),
              onClose: () => j(null),
            }),
          }),
        H?.type === "account" &&
          (0, s.jsx)(Ma, {
            title: H.account
              ? "Update the little details."
              : "Keep it somewhere safe.",
            subtitle: "A private account, connected to the right person.",
            onClose: () => j(null),
            wide: !0,
            children: (0, s.jsx)(lg, {
              account: H.account,
              contactId: H.contactId,
              people: e.contacts,
              onDone: () => od("Account saved and secrets encrypted."),
              onClose: () => j(null),
            }),
          }),
        H?.type === "journal" &&
          (0, s.jsx)(Ma, {
            title: H.entry
              ? "A little more reflection."
              : "Make a little space.",
            subtitle: "No audience. No expectations. Just you.",
            onClose: () => j(null),
            wide: !0,
            children: (0, s.jsx)(ug, {
              entry: H.entry,
              contactId: H.contactId,
              people: e.contacts,
              onDone: () => od("Your entry is saved, just for you."),
              onClose: () => j(null),
            }),
          }),
        H?.type === "birthdays" &&
          (0, s.jsx)(Ma, {
            title: "Their day, remembered.",
            subtitle: "A thoughtful little hello goes a long way.",
            onClose: () => j(null),
            children: (0, s.jsx)("div", {
              className: "all-birthdays",
              children: Zs.length
                ? Zs.map((i) =>
                    (0, s.jsxs)(
                      "button",
                      {
                        className: "birthday-person",
                        onClick: () => {
                          (j(null), Tl(i));
                        },
                        children: [
                          (0, s.jsx)(it, { person: i, size: "md" }),
                          (0, s.jsxs)("span", {
                            children: [
                              (0, s.jsx)("strong", { children: i.name }),
                              (0, s.jsxs)("small", {
                                children: [
                                  $t(i.dob),
                                  " \xB7 Turning ",
                                  (ka(i.dob) || 0) + (Ne(i.dob) > 0 ? 1 : 0),
                                ],
                              }),
                            ],
                          }),
                          (0, s.jsx)("span", {
                            className: `birthday-countdown ${Ne(i.dob) <= 3 ? "soon" : ""}`,
                            children:
                              Ne(i.dob) === 0
                                ? "Today!"
                                : `In ${Ne(i.dob)} days`,
                          }),
                        ],
                      },
                      i.id,
                    ),
                  )
                : (0, s.jsxs)("div", {
                    className: "detail-empty",
                    children: [
                      (0, s.jsx)(Mu, { size: 30 }),
                      (0, s.jsx)("p", {
                        children:
                          "Add a date of birth to a contact to see their birthday here.",
                      }),
                    ],
                  }),
            }),
          }),
        H?.type === "delete" &&
          (0, s.jsx)(Ma, {
            title: "Let this one go?",
            onClose: () => j(null),
            children: (0, s.jsxs)("div", {
              className: "delete-content",
              children: [
                (0, s.jsx)("span", {
                  className: "delete-icon",
                  children: (0, s.jsx)(oa, { size: 26 }),
                }),
                (0, s.jsxs)("p", {
                  children: [
                    "Are you sure you want to delete ",
                    (0, s.jsx)("strong", { children: H.name }),
                    "?",
                  ],
                }),
                (0, s.jsx)("p", {
                  className: "muted",
                  children:
                    H.kind === "Contact"
                      ? "Their linked accounts and private journal entries will also be permanently deleted."
                      : "This is permanent and can\u2019t be undone.",
                }),
                (0, s.jsxs)("div", {
                  className: "form-footer",
                  children: [
                    (0, s.jsx)("button", {
                      className: "button secondary",
                      onClick: () => j(null),
                      children: "Keep it",
                    }),
                    (0, s.jsxs)("button", {
                      className: "button danger",
                      disabled: ji,
                      onClick: hg,
                      children: [
                        ji
                          ? (0, s.jsx)(Re, { size: 15, className: "spin" })
                          : (0, s.jsx)(oa, { size: 15 }),
                        "Delete ",
                        H.kind.toLowerCase(),
                      ],
                    }),
                  ],
                }),
              ],
            }),
          }),
        H?.type === "journalView" &&
          (0, s.jsx)(Ma, {
            title: `A moment with ${e.contacts.find((i) => i.id === H.entry.contactId)?.name.split(" ")[0] || "someone special"}.`,
            subtitle: `${$t(H.entry.date)}, ${H.entry.date.slice(0, 4)} \xB7 ${H.entry.type} \xB7 ${H.entry.mood}`,
            onClose: () => j(null),
            wide: !0,
            children: (0, s.jsxs)("div", {
              className: "journal-reading",
              children: [
                (0, s.jsx)("p", {
                  className: "journal-main-note",
                  children: H.entry.notes,
                }),
                H.entry.type === "Intimacy" &&
                  [
                    { label: "Consent", value: H.entry.consent },
                    {
                      label: "Communication & boundaries",
                      value: H.entry.communication,
                    },
                    {
                      label: "Protection & sexual health",
                      value: H.entry.protection,
                    },
                    {
                      label: "Emotional & physical wellbeing",
                      value: H.entry.wellbeing,
                    },
                    { label: "Aftercare & follow-up", value: H.entry.followUp },
                  ].map((i) =>
                    (0, s.jsxs)(
                      "div",
                      {
                        className: "reading-answer",
                        children: [
                          (0, s.jsx)("h3", { children: i.label }),
                          (0, s.jsx)("p", {
                            children: i.value || "Not recorded",
                          }),
                        ],
                      },
                      i.label,
                    ),
                  ),
                (0, s.jsxs)("div", {
                  className: "form-footer",
                  children: [
                    (0, s.jsxs)("button", {
                      className: "text-button danger-text",
                      onClick: () =>
                        j({
                          type: "delete",
                          kind: "Journal",
                          id: H.entry.id,
                          name: "this private entry",
                        }),
                      children: [(0, s.jsx)(oa, { size: 14 }), "Delete entry"],
                    }),
                    (0, s.jsxs)("button", {
                      className: "button primary",
                      onClick: () => j({ type: "journal", entry: H.entry }),
                      children: [
                        (0, s.jsx)(Qt, { size: 14 }),
                        "Edit reflection",
                      ],
                    }),
                  ],
                }),
                (0, s.jsx)(Dl, {}),
              ],
            }),
          }),
        H?.type === "help" &&
          (0, s.jsx)(Ma, {
            title: "A little guidance.",
            subtitle: "An easier way to keep your people close.",
            onClose: () => j(null),
            children: (0, s.jsxs)("div", {
              className: "help-content",
              children: [
                [
                  {
                    icon: Wt,
                    title: "Start with your people",
                    text: "Add a contact, choose a circle, and keep their details together. Their age updates automatically from their date of birth.",
                  },
                  {
                    icon: Jt,
                    title: "Keep a few a little closer",
                    text: "Star a contact to add them to Favorites. Search by name, email, or phone with \u2318/Ctrl + K.",
                  },
                  {
                    icon: ne,
                    title: "Make the private parts yours",
                    text: "Finish setup to secure the workspace with a password. Then add encrypted accounts and private journal entries to any person.",
                  },
                  {
                    icon: Xe,
                    title: "A thoughtful boundary",
                    text: "Only store personal information with permission. Intimacy entries require adult confirmation. Export contacts without exposing your accounts or journal.",
                  },
                ].map(({ icon: i, title: M, text: z }) =>
                  (0, s.jsxs)(
                    "div",
                    {
                      children: [
                        (0, s.jsx)(i, { size: 20 }),
                        (0, s.jsxs)("span", {
                          children: [
                            (0, s.jsx)("h3", { children: M }),
                            (0, s.jsx)("p", { children: z }),
                          ],
                        }),
                      ],
                    },
                    M,
                  ),
                ),
                (0, s.jsxs)("button", {
                  className: "button primary full-width",
                  onClick: () => j(null),
                  children: [
                    "Make yourself at home ",
                    (0, s.jsx)(Ca, { size: 15 }),
                  ],
                }),
              ],
            }),
          }),
        Xi &&
          (0, s.jsx)("div", {
            className:
              typeof window < "u" && window.__INSTALL__ ? "installer-page" : "",
            children: (0, s.jsx)(og, {
              onClose: () => {
                window.__INSTALL__ || el(!1);
              },
              onDone: al,
            }),
          }),
        ad &&
          (0, s.jsxs)("div", {
            className: "toast",
            role: "status",
            children: [
              (0, s.jsx)("span", {
                className: "toast-icon",
                children: (0, s.jsx)(la, { size: 15 }),
              }),
              (0, s.jsx)("span", { children: ad }),
              (0, s.jsx)("button", {
                "aria-label": "Dismiss notification",
                onClick: () => td(""),
                children: (0, s.jsx)(Fa, { size: 15 }),
              }),
            ],
          }),
      ],
    });
  }
  function _s({ icon: e, label: a, value: t, href: l }) {
    return (0, s.jsxs)("div", {
      className: "detail-item",
      children: [
        (0, s.jsx)("span", { children: e }),
        (0, s.jsxs)("div", {
          children: [
            (0, s.jsx)("small", { children: a }),
            l
              ? (0, s.jsx)("a", { href: l, children: t })
              : (0, s.jsx)("p", { children: t || "Not added" }),
          ],
        }),
      ],
    });
  }
  function fg({ account: e, person: a, notify: t, onEdit: l, onDelete: u }) {
    let [o, d] = (0, q.useState)(null),
      [f, n] = (0, q.useState)(!1);
    (0, q.useEffect)(() => {
      if (!o) return;
      let m = setTimeout(() => d(null), 3e4);
      return () => clearTimeout(m);
    }, [o]);
    async function p() {
      if (o) {
        d(null);
        return;
      }
      n(!0);
      try {
        let m = await De("revealAccount", { id: e.id });
        d(m.account);
      } catch (m) {
        t(m.message);
      } finally {
        n(!1);
      }
    }
    async function h(m) {
      try {
        (await navigator.clipboard.writeText(m),
          t("Copied. Remember to clear your clipboard when you\u2019re done."));
      } catch {
        t(
          "Clipboard access isn\u2019t available. You can select and copy the visible value.",
        );
      }
    }
    let x = (m, L) =>
      (0, s.jsxs)("div", {
        className: "vault-field",
        children: [
          (0, s.jsx)("span", { children: m }),
          (0, s.jsx)("strong", {
            className: o ? "" : "masked",
            children: o
              ? o[L] || "Not added"
              : "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022",
          }),
          o?.[L] &&
            (0, s.jsx)("button", {
              className: "icon-button",
              "aria-label": `Copy ${m.toLowerCase()}`,
              onClick: () => h(o[L] || ""),
              children: (0, s.jsx)(Fo, { size: 13 }),
            }),
        ],
      });
    return (0, s.jsxs)("article", {
      className: "vault-card",
      children: [
        (0, s.jsxs)("div", {
          className: "vault-card-heading",
          children: [
            (0, s.jsx)("span", {
              className: "vault-icon",
              children:
                e.type === "Bank account"
                  ? (0, s.jsx)(Al, { size: 22 })
                  : (0, s.jsx)(bl, { size: 22 }),
            }),
            (0, s.jsxs)("div", {
              children: [
                (0, s.jsx)("h3", { children: e.title }),
                (0, s.jsx)("span", { children: e.type }),
              ],
            }),
            (0, s.jsx)("button", {
              className: "icon-button",
              "aria-label": "Edit account",
              onClick: l,
              children: (0, s.jsx)(Qt, { size: 15 }),
            }),
          ],
        }),
        a &&
          (0, s.jsxs)("div", {
            className: "vault-person",
            children: [(0, s.jsx)(it, { person: a, size: "xs" }), a.name],
          }),
        (0, s.jsxs)("div", {
          className: "vault-fields",
          children: [
            (0, s.jsxs)("div", {
              className: "vault-field",
              children: [
                (0, s.jsx)("span", { children: "Username" }),
                (0, s.jsx)("strong", { children: e.username || "Not added" }),
              ],
            }),
            (0, s.jsxs)("div", {
              className: "vault-field",
              children: [
                (0, s.jsx)("span", { children: "Email" }),
                (0, s.jsx)("strong", { children: e.email || "Not added" }),
              ],
            }),
            x("Password", "password"),
            e.type === "Bank account" &&
              (0, s.jsxs)(s.Fragment, {
                children: [
                  x("Routing no.", "routingNumber"),
                  x("Account no.", "accountNumber"),
                ],
              }),
            o?.notes &&
              (0, s.jsxs)("div", {
                className: "vault-notes",
                children: [
                  (0, s.jsx)("span", { children: "Private notes" }),
                  (0, s.jsx)("p", { children: o.notes }),
                ],
              }),
          ],
        }),
        (0, s.jsxs)("div", {
          className: "vault-card-footer",
          children: [
            (0, s.jsxs)("button", {
              className: "text-button",
              disabled: f,
              onClick: p,
              children: [
                f
                  ? (0, s.jsx)(Re, { size: 14, className: "spin" })
                  : o
                    ? (0, s.jsx)(Ua, { size: 14 })
                    : (0, s.jsx)(Ha, { size: 14 }),
                " ",
                o ? "Hide private details" : "Reveal private details",
              ],
            }),
            (0, s.jsx)("button", {
              className: "icon-button",
              onClick: u,
              "aria-label": "Delete account",
              children: (0, s.jsx)(oa, { size: 14 }),
            }),
          ],
        }),
        o &&
          (0, s.jsxs)("div", {
            className: "auto-hide-note",
            children: [
              (0, s.jsx)(ne, { size: 10 }),
              " Automatically hidden after 30 seconds",
            ],
          }),
      ],
    });
  }
  var rg = ce(da()),
    ng = document.getElementById("root");
  ng && (0, ig.createRoot)(ng).render((0, rg.jsx)(Gi, {}));
})();
/*! For license information please see app.js.LEGAL.txt */
