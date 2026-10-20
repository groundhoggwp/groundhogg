(() => {
  var __defProp = Object.defineProperty;
  var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

  // node_modules/preact/dist/preact.mjs
  var preact_exports = {};
  __export(preact_exports, {
    Component: () => $,
    Fragment: () => M,
    cloneElement: () => k,
    createContext: () => K,
    createElement: () => g,
    createPortal: () => R,
    createRef: () => m,
    h: () => g,
    hydrate: () => J,
    isValidElement: () => i,
    options: () => n,
    render: () => G,
    toChildArray: () => A
  });
  var n;
  var t;
  var i;
  var r;
  var u;
  var f;
  var o;
  var e;
  var l;
  var c;
  var a;
  var s;
  var h = {};
  var p = [];
  var v = /^m(i|n|o|s|text|space)$/;
  var y = Array.isArray;
  var d = p.slice;
  var w = Object.assign;
  function _(n2) {
    n2 && n2.parentNode && n2.remove();
  }
  function g(n2, t3, i3) {
    var r3, u4, f3, o4 = {}, e3 = arguments.length;
    for (f3 in t3) "key" == f3 ? r3 = t3[f3] : "ref" == f3 && "function" != typeof n2 ? u4 = t3[f3] : o4[f3] = t3[f3];
    return e3 > 2 && (o4.children = e3 > 3 ? d.call(arguments, 2) : i3), b(n2, o4, r3, u4, null);
  }
  function k(n2, t3, i3) {
    var r3, u4, f3, o4 = w({}, n2.props), e3 = arguments.length;
    for (f3 in t3) "key" == f3 ? r3 = t3[f3] : "ref" == f3 && "function" != typeof n2.type ? u4 = t3[f3] : o4[f3] = t3[f3];
    return e3 > 2 && (o4.children = e3 > 3 ? d.call(arguments, 2) : i3), b(n2.type, o4, void 0 !== r3 ? r3 : n2.key, void 0 !== u4 ? u4 : n2.ref, null);
  }
  function b(i3, r3, u4, f3, o4) {
    var e3 = { type: i3, props: r3, key: u4, ref: f3, __k: null, __: null, __b: 0, __e: null, __c: null, constructor: void 0, __v: o4 || ++t, __i: -1, __u: 0 };
    return !o4 && n.vnode && n.vnode(e3), e3;
  }
  function m() {
    return { current: null };
  }
  function M(n2) {
    return n2.children;
  }
  function $(n2, t3) {
    this.props = n2, this.context = t3, this.__g = 0;
  }
  function x(n2, t3) {
    if (null == t3) return n2.__ ? x(n2.__, n2.__i + 1) : null;
    for (var i3; t3 < n2.__k.length; t3++) if ((i3 = n2.__k[t3]) && i3.__e) return i3.__e;
    return "function" != typeof n2.type || n2.props.__P ? null : x(n2);
  }
  function S(n2) {
    if ((n2 = n2.__) && n2.__c && !n2.props.__P) return n2.__e = null, n2.__k.some(function(t3) {
      return t3 && (n2.__e = t3.__e);
    }), S(n2);
  }
  function C(t3) {
    (8 & t3.__g || !(t3.__g |= 8) || !r.push(t3) || f++) && u == n.debounceRendering || ((u = n.debounceRendering) || queueMicrotask)(j);
  }
  function j() {
    var t3, i3, u4, e3, l3, c3, a3, s3, h3;
    try {
      for (i3 = 1; r.length; ) r.length > i3 && r.sort(o), t3 = r.shift(), i3 = r.length, 8 & t3.__g && (e3 = void 0, l3 = void 0, c3 = (l3 = (u4 = t3).__v).__e, a3 = [], s3 = [], (h3 = u4.__P) && ((e3 = w({ constructor: void 0 }, l3)).__v = l3.__v + 1, n.vnode && n.vnode(e3), N(h3, e3, l3, u4.__n, h3.namespaceURI, 32 & l3.__u ? [c3] : null, a3, c3 || x(l3), 32 & l3.__u, s3), e3.__v = l3.__v, e3.__.__k[e3.__i] = e3, z(a3, e3, s3), l3.__ = l3.__e = null, e3.__e != c3 && S(e3)));
    } finally {
      r.length = f = 0;
    }
  }
  function L(n2, t3, i3, r3, u4, f3, o4, e3, l3, c3, a3) {
    var s3, v3, y3, d3, w3, _3, g3 = r3.__k || p, k3 = t3.length;
    for (l3 = H(i3, t3, g3, l3, k3), s3 = 0; s3 < k3; s3++) null != (y3 = i3.__k[s3]) && (v3 = ~y3.__i && g3[y3.__i] || h, y3.__i = s3, _3 = N(n2, y3, v3, u4, f3, o4, e3, l3, c3, a3), d3 = y3.__e, (v3.ref != y3.ref || 8 & v3.__u) && (v3.ref && D(v3.ref, null, y3, v3), y3.ref && a3.push(y3.ref, y3.__c || d3, y3)), w3 = w3 || d3, 4 & y3.__u ? (l3 = I(y3, l3, n2, !v3.__v), v3.__e && (v3.__e = null)) : "function" == typeof y3.type && void 0 !== _3 ? l3 = _3 : d3 && (l3 = d3.nextSibling), y3.__u &= -7);
    return i3.__e = w3, l3;
  }
  function H(n2, t3, i3, r3, u4) {
    var f3, o4, e3, l3, c3, a3, s3, h3, p3, v3, d3 = i3.length, w3 = d3, _3 = 0, g3 = false, k3 = n2.__k = Array(u4);
    for (f3 = 0; f3 < u4; f3++) null != (o4 = t3[f3]) && "boolean" != typeof o4 && "function" != typeof o4 ? ("object" != typeof o4 || o4.constructor == String ? o4 = k3[f3] = b(null, o4) : y(o4) ? o4 = k3[f3] = b(M, { children: o4 }) : void 0 === o4.constructor && o4.__b ? o4 = k3[f3] = b(o4.type, o4.props, o4.key, o4.ref, o4.__v) : k3[f3] = o4, l3 = f3 + _3, o4.__ = n2, o4.__b = n2.__b + 1, e3 = null, ~(c3 = o4.__i = O(o4, i3, l3, w3)) && (w3--, (e3 = i3[c3]) && (e3.__u |= 2)), e3 && e3.__v ? (o4.__u |= 2, c3 == l3 - 1 ? _3-- : c3 == l3 + 1 ? _3++ : c3 != l3 && (c3 > l3 ? _3-- : _3++, g3 = true)) : (~c3 || (u4 > d3 ? _3-- : u4 < d3 && _3++), "function" != typeof o4.type && (o4.__u |= 4))) : k3[f3] = null;
    if (g3) {
      for (a3 = [], s3 = [], f3 = 0; f3 < u4; f3++) if ((o4 = k3[f3]) && 2 & o4.__u) {
        for (h3 = 0, p3 = a3.length; h3 < p3; ) a3[v3 = h3 + p3 >> 1] < o4.__i ? h3 = v3 + 1 : p3 = v3;
        a3[h3] = o4.__i, s3[f3] = h3 + 1;
      }
      for (_3 = a3.length; f3--; ) s3[f3] && (s3[f3] == _3 ? _3-- : k3[f3].__u |= 4);
    }
    if (w3) for (f3 = 0; f3 < d3; f3++) !(e3 = i3[f3]) || 2 & e3.__u || (e3.__e == r3 && (r3 = x(e3)), E(e3, e3));
    return r3;
  }
  function I(n2, t3, i3, r3) {
    var u4, f3, o4;
    if ("function" == typeof n2.type) {
      if (n2.props.__P) return t3;
      if (u4 = n2.__k) for (f3 = 0; f3 < u4.length; f3++) u4[f3] && (u4[f3].__ = n2, t3 = I(u4[f3], t3, i3, false));
      return t3;
    }
    for (t3 && !t3.parentNode && (t3 = x(n2)) && !t3.parentNode && (t3 = null), o4 = t3; o4 && 8 == o4.nodeType; ) o4 = o4.nextSibling;
    for (n2.__e != o4 && (!r3 && i3.moveBefore && n2.__e.parentNode ? i3.moveBefore(n2.__e, t3) : i3.insertBefore(n2.__e, t3 || null)), t3 = n2.__e; (t3 = t3 && t3.nextSibling) && 8 == t3.nodeType; ) ;
    return t3;
  }
  function A(n2, t3) {
    return t3 = t3 || [], null != n2 && "boolean" != typeof n2 && (y(n2) ? n2.some(function(n3) {
      A(n3, t3);
    }) : t3.push(n2)), t3;
  }
  function O(n2, t3, i3, r3) {
    var u4, f3, o4, e3 = n2.key, l3 = n2.type, c3 = t3[i3], a3 = c3 && !(2 & c3.__u);
    if (null === c3 && null == e3 || a3 && e3 == c3.key && l3 == c3.type) return i3;
    if (r3 > (a3 ? 1 : 0)) {
      for (u4 = i3 - 1, f3 = i3 + 1; u4 >= 0 || f3 < t3.length; ) if ((c3 = t3[o4 = u4 >= 0 ? u4-- : f3++]) && !(2 & c3.__u) && e3 == c3.key && l3 == c3.type) return o4;
    }
    return -1;
  }
  function P(n2, t3, i3) {
    null == i3 && (i3 = ""), "-" == t3[0] ? n2.setProperty(t3, i3) : n2[t3] = i3;
  }
  function T(n2, t3, i3, r3, u4) {
    var f3, o4;
    n: if ("style" == t3) if ("string" == typeof i3) n2.style.cssText = i3;
    else {
      if ("string" == typeof r3 && (n2.style.cssText = r3 = ""), r3) for (t3 in r3) i3 && t3 in i3 || P(n2.style, t3, "");
      if (i3) for (t3 in i3) r3 && i3[t3] == r3[t3] || P(n2.style, t3, i3[t3]);
    }
    else if ("o" == t3[0] && "n" == t3[1]) (n2.__e || (n2.__e = {}))[t3] = i3, i3 && r3 || (o4 = a[t3] || (a[t3] = q(t3)), (n2.__a || (n2.__a = {}))[t3] = c, f3 = t3 != (t3 = t3.replace(l, "$1")), (t3 = t3.slice(2))[0] < "a" && (t3 = t3.toLowerCase()), i3 ? n2.addEventListener(t3, o4, f3) : n2.removeEventListener(t3, o4, f3));
    else {
      if ("http://www.w3.org/2000/svg" == u4) t3 = t3.replace(/xlink(H|:h)/, "h").replace(/sName$/, "s");
      else if ("width" != t3 && "height" != t3 && "href" != t3 && "list" != t3 && "form" != t3 && "tabIndex" != t3 && "download" != t3 && "rowSpan" != t3 && "colSpan" != t3 && "role" != t3 && "popover" != t3 && t3 in n2) try {
        n2[t3] = null == i3 ? "" : i3;
        break n;
      } catch (n3) {
      }
      "function" == typeof i3 || (null == i3 || false === i3 && "-" != t3[4] ? n2.removeAttribute(t3) : n2.setAttribute(t3, "popover" == t3 && 1 == i3 ? "" : i3));
    }
  }
  function q(t3) {
    return function(i3) {
      if (this.__e) {
        var r3 = this.__e[t3];
        if (null == i3[e]) i3[e] = c++;
        else if (i3[e] < this.__a[t3]) return;
        return r3(n.event ? n.event(i3) : i3);
      }
    };
  }
  function N(t3, i3, r3, u4, f3, o4, e3, l3, c3, a3) {
    var s3, h3, v3, d3, g3, k3, b3, m3, S2, C3, j3, H2, I2, A3, O3, P3, T3, q3, N2, z3, D3 = i3.type;
    if (void 0 !== i3.constructor) return null;
    if (128 & r3.__u && (c3 = 32 & r3.__u, s3 = r3.__c.__z)) {
      if (i3.__u |= c3, h3 = o4 = [], 8 == s3.nodeType) for (v3 = 1, d3 = s3.nextSibling; d3; d3 = d3.nextSibling) {
        if (8 == d3.nodeType) {
          if (d3.data.startsWith("$s")) v3++;
          else if (d3.data.startsWith("/$s") && !--v3) break;
        }
        o4.push(d3);
      }
      else o4.push(s3);
      l3 = o4[0];
    }
    (s3 = n.__b) && s3(i3);
    n: if ("function" == typeof D3) {
      g3 = e3.length;
      try {
        if (C3 = i3.props, j3 = (s3 = D3.prototype) && s3.render, H2 = (s3 = D3.contextType) && u4[s3.__c], I2 = s3 ? H2 ? H2.props.value : s3.__ : u4, r3.__c ? 2 & (k3 = i3.__c = r3.__c).__g && (k3.__g |= 1) : (j3 ? i3.__c = k3 = new D3(C3, I2) : (i3.__c = k3 = new $(C3, I2), k3.constructor = D3, k3.render = F), H2 && H2.sub(k3), k3.state || (k3.state = {}), k3.__n = u4, k3.__g |= 8, k3.__h = [], k3.__k = []), j3 && (k3.__s || (k3.__s = k3.state), D3.getDerivedStateFromProps && (k3.__s == k3.state && (k3.__s = w({}, k3.__s)), w(k3.__s, D3.getDerivedStateFromProps(C3, k3.__s)))), b3 = k3.props, m3 = k3.state, k3.__v = i3, r3.__c) {
          if (j3 && !D3.getDerivedStateFromProps && C3 !== b3 && k3.componentWillReceiveProps && k3.componentWillReceiveProps(C3, I2), i3.__v == r3.__v && !(8 & k3.__g) || !(4 & k3.__g) && k3.shouldComponentUpdate && false === k3.shouldComponentUpdate(C3, k3.__s, I2)) {
            i3.__v != r3.__v && (k3.props = C3, k3.state = k3.__s, k3.__g &= -9), i3.__e = r3.__e, i3.__k = r3.__k, i3.__k.some(function(n2) {
              n2 && (n2.__ = i3);
            }), p.push.apply(k3.__h, k3.__k), k3.__k = [], k3.__h.length && e3.push(k3), l3 = x(r3);
            break n;
          }
          k3.componentWillUpdate && k3.componentWillUpdate(C3, k3.__s, I2), j3 && k3.componentDidUpdate && k3.__h.push(function() {
            k3.componentDidUpdate(b3, m3, S2);
          });
        } else j3 && !D3.getDerivedStateFromProps && k3.componentWillMount && k3.componentWillMount(), j3 && k3.componentDidMount && k3.__h.push(k3.componentDidMount);
        if (k3.context = I2, k3.props = C3, k3.__P = t3, k3.__g &= -5, A3 = n.__r, O3 = 0, j3) k3.state = k3.__s, k3.__g &= -9, A3 && A3(i3), s3 = k3.render(k3.props, k3.state, k3.context), p.push.apply(k3.__h, k3.__k), k3.__k = [];
        else do {
          k3.__g &= -9, A3 && A3(i3), s3 = k3.render(k3.props, k3.state, k3.context), k3.state = k3.__s;
        } while (8 & k3.__g && ++O3 < 25);
        k3.state = k3.__s, k3.getChildContext && (u4 = w({}, u4, k3.getChildContext())), j3 && r3.__c && k3.getSnapshotBeforeUpdate && (S2 = k3.getSnapshotBeforeUpdate(b3, m3)), P3 = s3 && s3.type === M && null == s3.key ? s3.props.children : s3, C3.__P && (s3 = l3, f3 = (t3 = C3.__P).namespaceURI, c3 = o4 = null, r3.props && r3.props.__P != t3 && (r3.__k.some(function(n2) {
          n2 && E(n2, n2);
        }), r3.__k = null), l3 = r3.__k ? x(r3, 0) : null), l3 = L(t3, y(P3) ? P3 : [P3], i3, r3, u4, f3, o4, e3, l3, c3, a3), C3.__P && (i3.__e = null, l3 = s3), i3.__u &= -161, 128 & r3.__u && (k3.__z = null), h3 && h3.some(_), k3.__h.length && e3.push(k3), 1 & k3.__g && (k3.__g &= -4);
      } catch (t4) {
        if (e3.length = g3, i3.__v = null, c3 || o4) if (t4.then) {
          if (T3 = 0, i3.__u |= c3 ? 160 : 128, o4) {
            for (~(N2 = o4.indexOf(l3 || void 0)) || (N2 = o4.length); (z3 = o4[N2 - 1]) && 8 == z3.nodeType; ) N2--;
            for (; N2 < o4.length; N2++) if (z3 = o4[N2]) {
              if (o4[N2] = null, 8 == z3.nodeType) {
                if (z3.data.startsWith("$s")) T3++ || (q3 = z3);
                else if (T3 && z3.data.startsWith("/$s") && !--T3) {
                  l3 = z3;
                  break;
                }
              } else if (!T3) break;
            }
          }
          if (!q3) {
            for (; l3 && 8 == l3.nodeType && l3.nextSibling; ) l3 = l3.nextSibling;
            q3 = l3;
          }
          i3.__c.__z || (i3.__c.__z = q3), i3.__e = l3;
        } else o4 && o4.some(_);
        else i3.__e = r3.__e;
        i3.__k || (i3.__k = r3.__k || []), t4.then || V(i3), n.__e(t4, i3, r3);
      }
    } else l3 = i3.__e = B(r3.__e, i3, r3, u4, f3, o4, e3, c3, a3, t3);
    return (s3 = n.diffed) && s3(i3), 128 & i3.__u ? void 0 : l3;
  }
  function V(n2) {
    n2 && (n2.__c && (n2.__c.__g |= 4), n2.__k && n2.__k.some(V));
  }
  function z(t3, i3, r3) {
    for (var u4 = 0; u4 < r3.length; ) D(r3[u4++], r3[u4++], r3[u4++]);
    n.__c && n.__c(i3, t3), t3.some(function(i4) {
      try {
        t3 = i4.__h, i4.__h = [], t3.some(function(n2) {
          n2.call(i4);
        });
      } catch (t4) {
        n.__e(t4, i4.__v);
      }
    });
  }
  function B(t3, i3, r3, u4, f3, o4, e3, l3, c3, a3) {
    var s3, p3, w3, g3, k3, b3, m3, M2, $2, S2 = r3.props || h, C3 = i3.props, j3 = i3.type;
    if ("svg" == j3 ? f3 = "http://www.w3.org/2000/svg" : "math" == j3 ? f3 = "http://www.w3.org/1998/Math/MathML" : f3 || (f3 = "http://www.w3.org/1999/xhtml"), o4) {
      for (s3 = 0; s3 < o4.length; s3++) if ((k3 = o4[s3]) && (j3 ? k3.localName == j3 : 3 == k3.nodeType)) {
        t3 = k3, o4[s3] = null;
        break;
      }
    }
    if (!t3) {
      if (M2 = a3.ownerDocument || document, !j3) return M2.createTextNode(C3);
      t3 = M2.createElementNS(f3, j3, C3.is && C3), l3 && (n.__m && n.__m(i3, o4), l3 = false), o4 = null;
    }
    if (j3) {
      if (a3 = "template" == j3 ? t3.content : t3, o4 = "textarea" == j3 && null != C3.defaultValue ? null : o4 && d.call(a3.childNodes), !l3 && o4) for (S2 = {}, s3 = 0; s3 < t3.attributes.length; s3++) S2[(k3 = t3.attributes[s3]).name] = k3.value;
      for (s3 in S2) k3 = S2[s3], "dangerouslySetInnerHTML" == s3 ? w3 = k3 : "children" == s3 || s3 in C3 || "value" == s3 && "defaultValue" in C3 || "checked" == s3 && "defaultChecked" in C3 || T(t3, s3, null, k3, f3);
      for (s3 in $2 = 1 & r3.__u, C3) k3 = C3[s3], "children" == s3 ? g3 = k3 : "dangerouslySetInnerHTML" == s3 ? p3 = k3 : "value" == s3 ? b3 = k3 : "checked" == s3 ? m3 = k3 : l3 && "function" != typeof k3 || !(S2[s3] !== k3 || $2 && null != k3) || T(t3, s3, k3, S2[s3], f3);
      p3 ? (l3 || w3 && (p3.__html == w3.__html || p3.__html == t3.innerHTML) || (t3.innerHTML = p3.__html), i3.__k = []) : (w3 && (t3.textContent = ""), ("foreignObject" == j3 || "http://www.w3.org/1998/Math/MathML" == f3 && v.test(j3)) && (f3 = "http://www.w3.org/1999/xhtml"), L(a3, y(g3) ? g3 : [g3], i3, r3, u4, f3, o4, e3, o4 ? o4[0] : r3.__k && x(r3, 0), l3, c3), o4 && o4.some(_)), l3 && "textarea" != j3 || (s3 = "value", "progress" == j3 && null == b3 ? t3.removeAttribute(s3) : null == b3 || b3 === t3[s3] && ("progress" != j3 || b3) || T(t3, s3, b3, S2[s3], f3), s3 = "checked", null != m3 && m3 != t3[s3] && T(t3, s3, m3, S2[s3], f3));
    } else S2 === C3 || l3 && t3.data == C3 || (t3.data = C3);
    return t3;
  }
  function D(t3, i3, r3, u4) {
    try {
      "function" == typeof t3 ? i3 ? i3.__x = t3(i3) || 1 : u4 && (u4 = u4.__c || u4.__e) && (i3 = u4.__x) && (u4.__x = null, "function" == typeof i3 ? i3() : t3(null)) : t3.current = i3;
    } catch (t4) {
      n.__e(t4, r3);
    }
  }
  function E(t3, i3, r3) {
    var u4, f3;
    if (n.unmount && n.unmount(t3), !(u4 = t3.ref) || u4.current && u4.current != t3.__e || D(u4, null, i3, t3), u4 = t3.__c) {
      if (u4.componentWillUnmount) try {
        u4.componentWillUnmount();
      } catch (t4) {
        n.__e(t4, i3);
      }
      u4.__P = u4.__n = null;
    }
    if (u4 = t3.__k) for (f3 = 0; f3 < u4.length; f3++) u4[f3] && E(u4[f3], i3, "function" != typeof t3.type || r3 && !t3.props.__P);
    (u4 = t3.__e) && (r3 || _(u4), u4.__e && (u4.__e = null)), t3.__e = t3.__c = t3.__ = null;
  }
  function F(n2, t3, i3) {
    return this.constructor(n2, i3);
  }
  function G(t3, i3) {
    var r3, u4, f3, o4;
    n.__ && n.__(t3, i3), 9 == i3.nodeType && (i3 = i3.documentElement), u4 = (r3 = t3 && 32 & t3.__u) ? null : i3.__k, i3.__k = b(M, { children: [t3] }), f3 = [], o4 = [], N(i3, i3.__k, u4 || h, h, i3.namespaceURI, u4 ? null : i3.firstChild ? d.call(i3.childNodes) : null, f3, u4 ? u4.__e : i3.firstChild, r3, o4), z(f3, i3.__k, o4), i3.__k.props.children = null;
  }
  function J(n2, t3) {
    n2 && "object" == typeof n2 && (n2.__u |= 32), G(n2, t3);
  }
  function K(n2) {
    function t3(n3) {
      var i3, r3;
      return this.getChildContext || (i3 = /* @__PURE__ */ new Set(), (r3 = {})[t3.__c] = this, this.getChildContext = function() {
        return r3;
      }, this.shouldComponentUpdate = function(n4) {
        this.props.value != n4.value && i3.forEach(function(n5) {
          n5.__g |= 4, C(n5);
        });
      }, this.sub = function(n4) {
        i3.add(n4);
        var t4 = n4.componentWillUnmount;
        n4.componentWillUnmount = function() {
          i3.delete(n4), t4 && t4.call(n4);
        };
      }), n3.children;
    }
    return t3.__c = "__cC" + s++, t3.__ = n2, t3.Provider = (t3.Consumer = function(n3, t4) {
      return n3.children(t4);
    }).contextType = t3, t3;
  }
  function Q(n2) {
    return n2.children;
  }
  function R(n2, t3) {
    return b(Q, { __P: t3, children: n2 });
  }
  n = { __e: function(n2, t3, i3, r3) {
    for (var u4, o4, e3; t3 = t3.__; ) if ((u4 = t3.__c) && !(1 & u4.__g)) {
      u4.__g |= 4;
      try {
        if ((o4 = u4.constructor) && o4.getDerivedStateFromError && (u4.setState(o4.getDerivedStateFromError(n2)), e3 = 8 & u4.__g), u4.componentDidCatch && (u4.componentDidCatch(n2, r3 || {}), e3 = 8 & u4.__g), e3) return void (u4.__g |= 2);
      } catch (t4) {
        n2 = t4, e3 = 0;
      }
    }
    throw f = 0, n2;
  } }, t = 0, i = function(n2) {
    return null != n2 && void 0 === n2.constructor;
  }, $.prototype.setState = function(n2, t3) {
    var i3 = this.__s;
    i3 && i3 != this.state || (i3 = this.__s = w({}, this.state)), "function" == typeof n2 && (n2 = n2(w({}, i3), this.props)), n2 && (w(i3, n2), this.__v && (t3 && this.__k.push(t3), C(this)));
  }, $.prototype.forceUpdate = function(n2) {
    this.__v && (this.__g |= 4, n2 && this.__h.push(n2), C(this));
  }, $.prototype.render = M, r = [], f = 0, o = function(n2, t3) {
    return n2.__v.__b - t3.__v.__b;
  }, e = /* @__PURE__ */ Symbol(), l = /(PointerCapture)$|Capture$/i, c = 0, a = {}, s = 0;

  // node_modules/preact/hooks/dist/hooks.mjs
  var hooks_exports = {};
  __export(hooks_exports, {
    useCallback: () => j2,
    useContext: () => w2,
    useDebugValue: () => x2,
    useEffect: () => A2,
    useErrorBoundary: () => O2,
    useId: () => P2,
    useImperativeHandle: () => q2,
    useLayoutEffect: () => F2,
    useMemo: () => b2,
    useReducer: () => _2,
    useRef: () => T2,
    useState: () => d2
  });
  var t2;
  var r2;
  var u2;
  var i2;
  var o2 = Object.is;
  var f2 = 0;
  var c2 = [];
  var e2 = [];
  var a2 = n;
  var v2 = a2.__b;
  var l2 = a2.__r;
  var m2 = a2.diffed;
  var s2 = a2.__c;
  var h2 = a2.unmount;
  var p2 = a2.__;
  function y2(n2, t3) {
    a2.__h && a2.__h(r2, n2, f2 || t3), f2 = 0;
    var u4 = r2.__H || (r2.__H = { __: [], __h: [] });
    return n2 >= u4.__.length && u4.__.push({}), u4.__[n2];
  }
  function d2(n2) {
    return f2 = 1, _2(G2, n2);
  }
  function _2(n2, u4, i3) {
    var f3 = y2(t2++, 2);
    if (f3.t = n2, !f3.__c && (f3.__ = [i3 ? i3(u4) : G2(void 0, u4), function(n3) {
      var t3 = f3.__N ? f3.__N[0] : f3.__[0], r3 = f3.t(t3, n3);
      o2(t3, r3) || (f3.__N = [r3, f3.__[1]], f3.__c.setState({}));
    }], f3.__c = r2, !r2.__f)) {
      r2.__f = true;
      var c3 = r2.shouldComponentUpdate;
      r2.shouldComponentUpdate = function(n3, t3, r3) {
        var u5 = this.__H;
        if (!u5) return true;
        var i4 = false, f4 = this.props != n3;
        if (u5.__.some(function(n4) {
          n4.__N && (i4 = true, o2(n4.__[0], n4.__N[0]) || (f4 = true));
        }), c3) {
          var e3 = c3.call(this, n3, t3, r3);
          return i4 ? e3 || f4 : e3;
        }
        return !i4 || f4;
      };
    }
    return f3.__;
  }
  function A2(n2, u4) {
    var i3 = y2(t2++, 3);
    !a2.__s && E2(i3.__H, u4) && (i3.__P = true, i3.__ = n2, i3.u = u4, r2.__H.__h.push(i3));
  }
  function F2(n2, u4) {
    var i3 = y2(t2++, 4);
    !a2.__s && E2(i3.__H, u4) && (i3.__P = false, i3.__ = n2, i3.u = u4, r2.__h.push(i3));
  }
  function T2(n2) {
    return f2 = 5, b2(function() {
      return { current: n2 };
    }, []);
  }
  function q2(n2, t3, r3) {
    f2 = 6, F2(function() {
      if ("function" == typeof n2) {
        var r4 = n2(t3());
        return function() {
          n2(null), r4 && "function" == typeof r4 && r4();
        };
      }
      if (n2) return n2.current = t3(), function() {
        return n2.current = null;
      };
    }, null == r3 ? r3 : r3.concat(n2));
  }
  function b2(n2, r3) {
    var u4 = y2(t2++, 7);
    return E2(u4.__H, r3) && (u4.__ = n2(), u4.__H = r3), u4.__;
  }
  function j2(n2, t3) {
    return f2 = 8, b2(function() {
      return n2;
    }, t3);
  }
  function w2(n2) {
    var u4 = r2.context[n2.__c], i3 = y2(t2++, 9);
    return i3.c = n2, u4 ? (null == i3.__ && (i3.__ = true, u4.sub(r2)), u4.props.value) : n2.__;
  }
  function x2(n2, t3) {
    a2.useDebugValue && a2.useDebugValue(t3 ? t3(n2) : n2);
  }
  function O2(n2) {
    var u4 = y2(t2++, 10), i3 = d2();
    return u4.__ = n2, r2.componentDidCatch || (r2.componentDidCatch = function(n3, t3) {
      u4.__ && u4.__(n3, t3), i3[1](n3);
    }), [i3[0], function() {
      i3[1](void 0);
    }];
  }
  function P2() {
    var n2 = y2(t2++, 11);
    if (!n2.__) {
      for (var u4 = r2.__v; !u4.__m && u4.__; ) u4 = u4.__;
      var i3 = u4.__m || (u4.__m = [0, 0]);
      n2.__ = "P" + i3[0] + "-" + i3[1]++;
    }
    return n2.__;
  }
  function g2() {
    var n2;
    do {
      for (; n2 = e2.shift(); ) try {
        C2(n2);
      } catch (t4) {
        a2.__e(t4, { __: (n2 = n2.__P) && n2.__v });
      }
      for (; n2 = c2.shift(); ) {
        var t3 = n2.__H;
        if (n2.__P && t3) try {
          t3.__h.some(C2), t3.__h.some(D2), t3.__h = [];
        } catch (r3) {
          t3.__h = [], a2.__e(r3, n2.__v);
        }
      }
    } while (e2.length);
  }
  a2.__b = function(n2) {
    r2 = null, v2 && v2(n2);
  }, a2.__ = function(n2, t3) {
    n2 && t3.__k && t3.__k.__m && (n2.__m = t3.__k.__m), p2 && p2(n2, t3);
  }, a2.__r = function(n2) {
    l2 && l2(n2), t2 = 0;
    var i3 = (r2 = n2.__c).__H;
    i3 && (u2 == r2 ? r2.__h = [] : (i3.__h.some(C2), i3.__h.some(D2), t2 = 0), i3.__h = [], i3.__.some(function(n3) {
      n3.__N && (n3.__ = n3.__N), n3.u = n3.__N = void 0;
    })), u2 = r2;
  }, a2.diffed = function(n2) {
    m2 && m2(n2);
    var t3 = n2.__c;
    t3 && t3.__H && (t3.__H.__h.length && B2(c2.push(t3)), t3.__H.__.some(function(n3) {
      n3.u && (n3.__H = n3.u);
    })), u2 = r2 = null;
  }, a2.__c = function(n2, t3) {
    t3.some(function(n3) {
      try {
        n3.__h.some(C2), n3.__h = n3.__h.filter(function(n4) {
          return !n4.__ || D2(n4);
        });
      } catch (r3) {
        t3.some(function(n4) {
          n4.__h && (n4.__h = []);
        }), t3 = [], a2.__e(r3, n3.__v);
      }
    }), s2 && s2(n2, t3);
  }, a2.unmount = function(n2) {
    h2 && h2(n2);
    var t3, r3, u4 = n2.__c;
    u4 && u4.__H && (u4.__H.__.some(function(u5) {
      try {
        if (u5.__P && u5.__c) {
          if (void 0 === r3) {
            for (r3 = n2.__; r3 && (!r3.__c || !r3.__c.__P); ) r3 = r3.__;
            r3 = r3 && r3.__c;
          }
          u5.__P = r3, B2(e2.push(u5));
        } else C2(u5);
      } catch (n3) {
        t3 = n3;
      }
    }), u4.__H = void 0, t3 && a2.__e(t3, u4.__v));
  };
  var k2 = "function" == typeof requestAnimationFrame;
  function z2(n2) {
    var t3, r3 = function() {
      clearTimeout(u4), k2 && cancelAnimationFrame(t3), setTimeout(n2);
    }, u4 = setTimeout(r3, 35);
    k2 && (t3 = requestAnimationFrame(r3));
  }
  function B2(n2) {
    1 != n2 && i2 == a2.requestAnimationFrame || ((i2 = a2.requestAnimationFrame) || z2)(g2);
  }
  function C2(n2) {
    var t3 = r2, u4 = n2.__c;
    "function" == typeof u4 && (n2.__c = void 0, u4()), r2 = t3;
  }
  function D2(n2) {
    var t3 = r2;
    n2.__c = n2.__(), r2 = t3;
  }
  function E2(n2, t3) {
    return !n2 || n2.length != t3.length || t3.some(function(t4, r3) {
      return !o2(t4, n2[r3]);
    });
  }
  function G2(n2, t3) {
    return "function" == typeof t3 ? t3(n2) : t3;
  }

  // src/core/api.js
  var ApiError = class extends Error {
    constructor(message, { status = 0, code = "", data = null } = {}) {
      super(message);
      this.name = "ApiError";
      this.status = status;
      this.code = code;
      this.data = data;
    }
  };
  function buildQuery(params = {}) {
    const parts = [];
    const add = (key, value) => {
      if (value === void 0 || value === null) {
        return;
      }
      if (Array.isArray(value)) {
        value.forEach((v3) => add(`${key}[]`, v3));
      } else if (typeof value === "object") {
        Object.keys(value).forEach((k3) => add(`${key}[${k3}]`, value[k3]));
      } else {
        const v3 = typeof value === "boolean" ? value ? 1 : 0 : value;
        parts.push(`${encodeURIComponent(key).replace(/%5B/g, "[").replace(/%5D/g, "]")}=${encodeURIComponent(v3)}`);
      }
    };
    Object.keys(params).forEach((k3) => add(k3, params[k3]));
    return parts.join("&");
  }
  function buildUrl(root, path, query) {
    const url = root.replace(/\/+$/, "") + "/" + String(path).replace(/^\/+/, "");
    const qs = buildQuery(query);
    if (!qs) {
      return url;
    }
    return url + (url.includes("?") ? "&" : "?") + qs;
  }
  function createApi({ root, nonce, nonceUrl, fetch: fetchFn = (...a3) => globalThis.fetch(...a3) }) {
    let currentNonce = nonce;
    let refreshing = null;
    const refreshNonce = () => {
      if (!refreshing) {
        refreshing = fetchFn(nonceUrl, { credentials: "same-origin" }).then((r3) => r3.ok ? r3.text() : "").then((text) => {
          text = String(text).trim();
          if (!/^[a-zA-Z0-9]{6,}$/.test(text)) {
            throw new ApiError("You have been logged out.", { status: 401, code: "logged_out" });
          }
          currentNonce = text;
        }).finally(() => {
          refreshing = null;
        });
      }
      return refreshing;
    };
    const request = async (method, path, { query, body } = {}, retried = false) => {
      const headers = { "X-WP-Nonce": currentNonce };
      const init = { method, headers, credentials: "same-origin" };
      if (body !== void 0) {
        headers["Content-Type"] = "application/json";
        init.body = JSON.stringify(body);
      }
      let res;
      try {
        res = await fetchFn(buildUrl(root, path, query), init);
      } catch (e3) {
        throw new ApiError("Could not reach the server.", { code: "network" });
      }
      let data = null;
      try {
        data = await res.json();
      } catch (e3) {
      }
      if (res.status === 403 && data && data.code === "rest_cookie_invalid_nonce" && !retried) {
        await refreshNonce();
        return request(method, path, { query, body }, true);
      }
      if (!res.ok) {
        throw new ApiError(data && data.message || `Request failed (${res.status}).`, {
          status: res.status,
          code: data && data.code || "",
          data
        });
      }
      if (data === null) {
        throw new ApiError("The server sent an unexpected response.", { status: res.status, code: "bad_response" });
      }
      return data;
    };
    return {
      request,
      refreshNonce,
      get: (path, query) => request("GET", path, { query }),
      post: (path, body) => request("POST", path, { body }),
      patch: (path, body) => request("PATCH", path, { body }),
      put: (path, body) => request("PUT", path, { body }),
      delete: (path, body) => request("DELETE", path, { body })
    };
  }

  // src/core/router.js
  function compile(pattern) {
    const keys = [];
    const source = pattern.replace(/\/+$/, "").split("/").map((segment) => {
      if (segment.startsWith(":")) {
        keys.push(segment.slice(1));
        return "([^/]+)";
      }
      return segment.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }).join("/");
    return { keys, regex: new RegExp(`^${source || ""}/?$`) };
  }
  function normalizePath(path) {
    path = "/" + String(path || "").replace(/^#?\/*/, "");
    return path.length > 1 ? path.replace(/\/+$/, "") : path;
  }
  function parseHash(hash) {
    const raw = String(hash || "").replace(/^#/, "");
    const [path, qs = ""] = raw.split("?");
    const query = {};
    new URLSearchParams(qs).forEach((value, key) => {
      query[key] = value;
    });
    return { path: normalizePath(path), query };
  }
  function matchRoute(routes, path) {
    path = normalizePath(path);
    const matches = [];
    for (const route of routes) {
      const { keys, regex } = compile(normalizePath(route.path));
      const m3 = regex.exec(path);
      if (!m3) {
        continue;
      }
      const params = {};
      keys.forEach((key, i3) => {
        params[key] = decodeURIComponent(m3[i3 + 1]);
      });
      matches.push({ route, params, score: keys.length });
    }
    matches.sort((a3, b3) => a3.score - b3.score);
    return matches.length ? { route: matches[0].route, params: matches[0].params } : null;
  }
  function firstSegment(path) {
    return normalizePath(path).split("/")[1] || "";
  }
  function toHash(path, query) {
    const qs = new URLSearchParams(query || {}).toString();
    return "#" + normalizePath(path) + (qs ? "?" + qs : "");
  }

  // src/core/registry.js
  var API_VERSION = 1;
  var IMPL_KEYS = {
    home_cards: "homeCards",
    contact_panels: "contactPanels",
    contact_actions: "contactActions",
    quick_create: "quickCreate",
    search_providers: "searchProviders"
  };
  function createRegistry(manifest, { warn = (...a3) => console.warn(...a3) } = {}) {
    const features = manifest.features || {};
    const impls = /* @__PURE__ */ new Map();
    const listeners = /* @__PURE__ */ new Set();
    const emit = () => listeners.forEach((fn) => fn());
    return {
      subscribe(fn) {
        listeners.add(fn);
        return () => listeners.delete(fn);
      },
      /**
       * @returns {boolean} whether the implementation was accepted
       */
      register(id, impl) {
        if (!features[id]) {
          warn(`Groundhogg mobile: "${id}" is not an available feature, ignoring it.`);
          return false;
        }
        if (impls.has(id)) {
          warn(`Groundhogg mobile: "${id}" is already registered.`);
          return false;
        }
        if ((impl && impl.apiVersion || 1) > API_VERSION) {
          warn(`Groundhogg mobile: "${id}" needs a newer mobile API than this site has.`);
          return false;
        }
        impls.set(id, impl || {});
        emit();
        return true;
      },
      has: (id) => impls.has(id),
      feature: (id) => features[id] || null,
      /**
       * Every visible contribution to an extension point across features, lowest priority first.
       */
      contributions(point) {
        const out = [];
        Object.keys(features).forEach((featureId) => {
          (features[featureId][point] || []).forEach((c3) => out.push({ ...c3, feature: featureId }));
        });
        return out.sort((a3, b3) => a3.priority - b3.priority || a3.id.localeCompare(b3.id));
      },
      /**
       * The component a feature supplied for one of its contributions, or null (not loaded yet, or declarative only).
       */
      implementation(featureId, point, id) {
        const impl = impls.get(featureId);
        const group = impl && impl[IMPL_KEYS[point]];
        return group && group[id] || null;
      },
      /**
       * Routes from every registered implementation.
       */
      routes() {
        const out = [];
        impls.forEach((impl, featureId) => {
          Object.keys(impl.routes || {}).forEach((path) => {
            const def = impl.routes[path];
            out.push({
              path,
              feature: featureId,
              component: typeof def === "function" ? def : def.component,
              title: typeof def === "function" ? "" : def.title || ""
            });
          });
        });
        return out;
      },
      /**
       * Which feature owns a path, from the first segment of the routes it declares in nav and search.
       * That lets the shell load a bundle the first time someone follows a deep link into its area.
       */
      featureForPath(path) {
        const segment = firstSegment(path);
        if (!segment) {
          return null;
        }
        for (const featureId of Object.keys(features)) {
          const declared = [...features[featureId].nav || [], ...features[featureId].search_providers || []];
          if (declared.some((c3) => c3.route && firstSegment(c3.route) === segment)) {
            return featureId;
          }
        }
        return null;
      }
    };
  }

  // src/core/loader.js
  function withVersion(src, version) {
    if (!version) {
      return src;
    }
    return src + (src.includes("?") ? "&" : "?") + "ver=" + encodeURIComponent(version);
  }
  function createLoader(registry, doc = document) {
    const pending = /* @__PURE__ */ new Map();
    const addStyle = (style) => {
      if (!style || !style.src) {
        return;
      }
      const link = doc.createElement("link");
      link.rel = "stylesheet";
      link.href = withVersion(style.src, style.version);
      doc.head.appendChild(link);
    };
    return {
      /**
       * @param {string} featureId
       * @returns {Promise<boolean>} whether the feature's implementation is registered afterwards
       */
      ensure(featureId) {
        if (registry.has(featureId)) {
          return Promise.resolve(true);
        }
        const feature = registry.feature(featureId);
        if (!feature || !feature.script) {
          return Promise.resolve(false);
        }
        if (!pending.has(featureId)) {
          addStyle(feature.style);
          pending.set(featureId, new Promise((resolve) => {
            const script = doc.createElement("script");
            script.src = withVersion(feature.script.src, feature.script.version);
            script.async = true;
            script.onload = () => resolve(registry.has(featureId));
            script.onerror = () => {
              pending.delete(featureId);
              resolve(false);
            };
            doc.head.appendChild(script);
          }));
        }
        return pending.get(featureId);
      }
    };
  }

  // src/core/i18n.js
  function sprintf(format, ...args) {
    let next = 0;
    return String(format).replace(/%(?:(\d+)\$)?([sd%])/g, (match, position, type) => {
      if (type === "%") {
        return "%";
      }
      const value = args[position ? Number(position) - 1 : next++];
      return type === "d" ? String(parseInt(value, 10) || 0) : String(value);
    });
  }
  function createI18n(data = {}, domain = "groundhogg") {
    const lookup = (key) => {
      const entry = data[domain] && data[domain][key];
      return Array.isArray(entry) ? entry : null;
    };
    return {
      domain,
      sprintf,
      __: (text) => (lookup(text) || [text])[0] || text,
      _x: (text, context) => (lookup(`${context}${text}`) || [text])[0] || text,
      _n: (single, plural, number) => {
        const entry = lookup(`${single}\0${plural}`);
        const index = number === 1 ? 0 : 1;
        return entry && entry[index] || (index === 0 ? single : plural);
      }
    };
  }

  // src/ui/index.jsx
  var ui_exports = {};
  __export(ui_exports, {
    Avatar: () => Avatar,
    Button: () => Button,
    Card: () => Card,
    Empty: () => Empty,
    ErrorMessage: () => ErrorMessage,
    Field: () => Field,
    Icon: () => Icon,
    Input: () => Input,
    List: () => List,
    ListItem: () => ListItem,
    Loading: () => Loading,
    Pill: () => Pill,
    Segmented: () => Segmented,
    Sheet: () => Sheet,
    Textarea: () => Textarea,
    Toast: () => Toast
  });

  // node_modules/preact/jsx-runtime/dist/jsxRuntime.mjs
  var o3 = 0;
  function u3(t3, e3, n2, f3, u4, i3) {
    e3 || (e3 = {});
    var a3, c3, l3 = e3;
    if ("ref" in l3 && "function" != typeof t3) for (c3 in l3 = {}, e3) "ref" == c3 ? a3 = e3[c3] : l3[c3] = e3[c3];
    var p3 = { type: t3, props: l3, key: n2, ref: a3, __k: null, __: null, __b: 0, __e: null, __c: null, constructor: void 0, __v: --o3, __i: -1, __u: 0 };
    return (u4 || i3) && (p3.__source = u4, p3.__self = i3), n.vnode && n.vnode(p3), p3;
  }

  // src/ui/icons.jsx
  var PATHS = {
    home: "M3 11.5 12 4l9 7.5M5.5 10v9.5a.5.5 0 0 0 .5.5h4v-6h4v6h4a.5.5 0 0 0 .5-.5V10",
    contacts: "M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM20 20v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.6a3.5 3.5 0 0 1 0 6.8",
    tasks: "M9 6h11M9 12h11M9 18h11M3.5 6l1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5",
    phone: "M21 16.5v3a1.5 1.5 0 0 1-1.6 1.5A17.5 17.5 0 0 1 3 4.6 1.5 1.5 0 0 1 4.5 3h3a1.5 1.5 0 0 1 1.5 1.3c.1 1 .4 1.9.7 2.8a1.5 1.5 0 0 1-.3 1.6L8.1 9.9a13.5 13.5 0 0 0 6 6l1.2-1.3a1.5 1.5 0 0 1 1.6-.3c.9.3 1.8.6 2.8.7a1.5 1.5 0 0 1 1.3 1.5Z",
    message: "M21 12a8 8 0 0 1-11.7 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z",
    mail: "M4 6h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm0 .5 8 6 8-6",
    note: "M6 3h9l4 4v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1ZM14 3v5h5M8.5 13h7M8.5 17h5",
    plus: "M12 5v14M5 12h14",
    search: "m20 20-3.8-3.8M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z",
    back: "m14.5 5-7 7 7 7",
    chevron: "m9.5 5 7 7-7 7",
    check: "m5 12.5 4.5 4.5L19 7.5",
    more: "M5 12h.01M12 12h.01M19 12h.01",
    menu: "M4 7h16M4 12h16M4 17h16",
    close: "M6 6l12 12M18 6 6 18",
    calendar: "M7 3v4M17 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z",
    deals: "M3 7h18v12H3zM8 7V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M3 12.5h18",
    building: "M5 21V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v17M15 9h3a1 1 0 0 1 1 1v11M3 21h18M9 7h2M9 11h2M9 15h2",
    external: "M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5",
    logout: "M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 16l-4-4 4-4M6 12h10",
    dot: "M12 12h.01"
  };
  function Icon({ name, size, ...props }) {
    return /* @__PURE__ */ u3("svg", { class: "ghm-icon", viewBox: "0 0 24 24", "aria-hidden": "true", width: size, height: size, ...props, children: /* @__PURE__ */ u3("path", { d: PATHS[name] || PATHS.dot }) });
  }
  var iconNames = Object.keys(PATHS);

  // src/ui/index.jsx
  var Button = ({ variant, block, icon, children, class: cls = "", ...props }) => /* @__PURE__ */ u3("button", { type: "button", class: `ghm-btn ${variant || ""} ${block ? "block" : ""} ${cls}`, ...props, children: [
    icon ? /* @__PURE__ */ u3(Icon, { name: icon }) : null,
    children
  ] });
  var Card = ({ title, action, children, ...props }) => /* @__PURE__ */ u3("section", { class: "ghm-card", ...props, children: [
    title ? /* @__PURE__ */ u3("h3", { class: "ghm-card-title", children: [
      /* @__PURE__ */ u3("span", { children: title }),
      action
    ] }) : null,
    children
  ] });
  var List = ({ children }) => /* @__PURE__ */ u3("ul", { class: "ghm-list", children });
  var ListItem = ({ title, subtitle, meta, leading, trailing, control, href, onClick }) => {
    const body = /* @__PURE__ */ u3(M, { children: [
      leading,
      /* @__PURE__ */ u3("div", { class: "ghm-item-body", children: [
        /* @__PURE__ */ u3("div", { class: "ghm-item-title", children: title }),
        subtitle ? /* @__PURE__ */ u3("div", { class: "ghm-item-sub", children: subtitle }) : null
      ] }),
      meta ? /* @__PURE__ */ u3("div", { class: "ghm-item-meta", children: meta }) : null,
      trailing
    ] });
    let row;
    if (href) {
      row = /* @__PURE__ */ u3("a", { class: "ghm-item", href, children: body });
    } else if (onClick) {
      row = /* @__PURE__ */ u3("button", { type: "button", class: "ghm-item", onClick, children: body });
    } else {
      row = /* @__PURE__ */ u3("div", { class: "ghm-item", style: "cursor:default", children: body });
    }
    return control ? /* @__PURE__ */ u3("li", { class: "ghm-item-wrap", children: [
      /* @__PURE__ */ u3("div", { class: "ghm-control", children: control }),
      row
    ] }) : /* @__PURE__ */ u3("li", { children: row });
  };
  var Empty = ({ children }) => /* @__PURE__ */ u3("div", { class: "ghm-empty", children });
  var Loading = ({ children = "\u2026" }) => /* @__PURE__ */ u3("div", { class: "ghm-loading", role: "status", children });
  var ErrorMessage = ({ error }) => error ? /* @__PURE__ */ u3("div", { class: "ghm-error", role: "alert", children: error.message || String(error) }) : null;
  var Pill = ({ color, children }) => /* @__PURE__ */ u3("span", { class: `ghm-pill ${color || ""}`, children });
  var Avatar = ({ name = "", src, large }) => {
    const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map((w3) => w3[0].toUpperCase()).join("");
    return /* @__PURE__ */ u3("div", { class: `ghm-avatar ${large ? "lg" : ""}`, children: [
      initials,
      src ? /* @__PURE__ */ u3("img", { src, alt: "", loading: "lazy", onError: (e3) => e3.currentTarget.remove() }) : null
    ] });
  };
  var Field = ({ label, children }) => /* @__PURE__ */ u3("label", { class: "ghm-field", children: [
    label ? /* @__PURE__ */ u3("span", { children: label }) : null,
    children
  ] });
  var Input = (props) => /* @__PURE__ */ u3("input", { class: "ghm-input", ...props });
  var Textarea = (props) => /* @__PURE__ */ u3("textarea", { class: "ghm-textarea", ...props });
  var Segmented = ({ options, value, onChange }) => /* @__PURE__ */ u3("div", { class: "ghm-seg", role: "tablist", children: options.map((o4) => /* @__PURE__ */ u3("button", { type: "button", role: "tab", "aria-selected": o4.value === value, class: o4.value === value ? "is-active" : "", onClick: () => onChange(o4.value), children: o4.label })) });
  var Sheet = ({ title, onClose, children }) => {
    const ref = T2(null);
    A2(() => {
      const onKey = (e3) => e3.key === "Escape" && onClose();
      document.addEventListener("keydown", onKey);
      ref.current && ref.current.focus();
      return () => document.removeEventListener("keydown", onKey);
    }, []);
    return /* @__PURE__ */ u3(M, { children: [
      /* @__PURE__ */ u3("div", { class: "ghm-scrim", onClick: onClose }),
      /* @__PURE__ */ u3("div", { class: "ghm-sheet", role: "dialog", "aria-modal": "true", "aria-label": title, tabIndex: -1, ref, children: [
        /* @__PURE__ */ u3("div", { class: "ghm-sheet-grab" }),
        title ? /* @__PURE__ */ u3("h2", { children: title }) : null,
        children
      ] })
    ] });
  };
  var Toast = ({ message, type }) => message ? /* @__PURE__ */ u3("div", { class: `ghm-toast ${type || ""}`, role: "status", children: message }) : null;

  // src/runtime.jsx
  var RuntimeContext = K(null);
  var useRuntime = () => w2(RuntimeContext);
  function createRuntime(manifest, shell = {}) {
    const api = createApi(manifest.rest);
    const registry = createRegistry(manifest);
    const loader = createLoader(registry);
    const caps = manifest.caps || {};
    const translations = manifest.i18n || {};
    const ctxCache = {};
    const ctxFor = (featureId = "core") => {
      if (!ctxCache[featureId]) {
        const feature = registry.feature(featureId) || {};
        const domain = feature.script && feature.script.domain || "groundhogg";
        ctxCache[featureId] = {
          api,
          ui: ui_exports,
          preact: { ...preact_exports, ...hooks_exports },
          i18n: createI18n(translations, domain),
          user: manifest.user,
          app: manifest.app,
          can: (cap) => !!caps[cap],
          get route() {
            return parseHash(window.location.hash);
          },
          navigate: (path, query) => {
            window.location.hash = toHash(path, query);
          },
          toast: (message, type) => shell.toast && shell.toast(message, type),
          sheet: (Component, props) => shell.sheet && shell.sheet(Component, props),
          confirm: (message) => Promise.resolve(window.confirm(message))
        };
      }
      return ctxCache[featureId];
    };
    const runtime = { manifest, api, registry, loader, ctxFor, shell };
    window.GroundhoggMobile = {
      apiVersion: API_VERSION,
      register: (id, impl) => registry.register(id, impl),
      ui: ui_exports,
      preact: { ...preact_exports, ...hooks_exports }
    };
    return runtime;
  }

  // src/contribution.jsx
  var ErrorBoundary = class extends $ {
    constructor() {
      super(...arguments);
      __publicField(this, "state", { error: null });
    }
    static getDerivedStateFromError(error) {
      return { error };
    }
    componentDidCatch(error) {
      console.error("Groundhogg mobile: a feature failed to render.", error);
    }
    render({ label, children }) {
      if (this.state.error) {
        return /* @__PURE__ */ u3("div", { class: "ghm-error", role: "alert", children: [
          label ? `${label}: ` : "",
          "couldn't load."
        ] });
      }
      return children;
    }
  };
  function useRegistryVersion() {
    const { registry } = useRuntime();
    const [, bump] = _2((n2) => n2 + 1, 0);
    A2(() => registry.subscribe(bump), [registry]);
  }
  function Contribution({ point, feature, id, label, fallback = null, ...rest }) {
    const { registry, loader, ctxFor } = useRuntime();
    useRegistryVersion();
    const Impl = registry.implementation(feature, point, id);
    A2(() => {
      if (!Impl) {
        loader.ensure(feature);
      }
    }, [Impl, feature]);
    if (!Impl) {
      return registry.feature(feature) && registry.feature(feature).script && !registry.has(feature) ? /* @__PURE__ */ u3(Loading, {}) : fallback;
    }
    return /* @__PURE__ */ u3(ErrorBoundary, { label, children: /* @__PURE__ */ u3(Impl, { ctx: ctxFor(feature), ...rest }) });
  }

  // src/core/template.js
  var TOKEN = /\{([^{}]+)\}/g;
  function contactValue(contact, key) {
    const data = contact && contact.data || {};
    const meta = contact && contact.meta || {};
    const value = data[key] !== void 0 && data[key] !== "" ? data[key] : meta[key];
    return value === void 0 || value === null ? "" : String(value).trim();
  }
  function resolveHref(template, contact) {
    if (!template) {
      return null;
    }
    const phone = /^(tel|sms):/i.test(template);
    let missing = false;
    const href = template.replace(TOKEN, (_3, spec) => {
      const value = spec.split("|").map((key) => contactValue(contact, key.trim())).find(Boolean);
      if (!value) {
        missing = true;
        return "";
      }
      return phone ? value.replace(/[^\d+]/g, "") : encodeURIComponent(value).replace(/%40/g, "@");
    });
    if (missing) {
      return null;
    }
    return /^(tel|sms|mailto|https?):/i.test(href) ? href : null;
  }

  // src/util.js
  function useFetch(load, deps = []) {
    const [state, setState] = d2({ data: null, error: null, loading: true });
    const latest = T2(0);
    const run = j2(() => {
      const id = ++latest.current;
      setState((s3) => ({ ...s3, loading: true, error: null }));
      return load().then(
        (data) => id === latest.current && setState({ data, error: null, loading: false }),
        (error) => id === latest.current && setState((s3) => ({ ...s3, error, loading: false }))
      );
    }, deps);
    A2(() => {
      run();
      return () => {
        latest.current++;
      };
    }, [run]);
    return { ...state, reload: run };
  }
  function useDebounced(value, ms = 250) {
    const [debounced, setDebounced] = d2(value);
    A2(() => {
      const t3 = setTimeout(() => setDebounced(value), ms);
      return () => clearTimeout(t3);
    }, [value, ms]);
    return debounced;
  }
  function plainText(html) {
    if (!html) {
      return "";
    }
    const doc = new DOMParser().parseFromString(String(html), "text/html");
    return (doc.body.textContent || "").trim();
  }
  function contactName(contact) {
    const d3 = contact && contact.data || {};
    return (d3.full_name || `${d3.first_name || ""} ${d3.last_name || ""}`).trim() || d3.email || "";
  }
  function avatarUrl(contact) {
    const url = contact && contact.data && contact.data.gravatar;
    if (!url) {
      return "";
    }
    return /[?&]d=/.test(url) ? url.replace(/([?&])d=[^&]*/, "$1d=404") : url + (url.includes("?") ? "&" : "?") + "d=404";
  }
  function itemOf(response) {
    return response.item || response.items && response.items[0] || null;
  }
  function toApiDate(inputValue) {
    return inputValue ? inputValue.replace("T", " ") + (inputValue.length === 16 ? ":00" : "") : "";
  }
  function tomorrowMorning(now = /* @__PURE__ */ new Date()) {
    const d3 = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 9, 0);
    const pad = (n2) => String(n2).padStart(2, "0");
    return `${d3.getFullYear()}-${pad(d3.getMonth() + 1)}-${pad(d3.getDate())}T09:00`;
  }

  // src/views/contacts.jsx
  var ContactRow = ({ contact }) => {
    const name = contactName(contact);
    const email = contact.data.email;
    return /* @__PURE__ */ u3(
      ListItem,
      {
        leading: /* @__PURE__ */ u3(Avatar, { name, src: avatarUrl(contact) }),
        title: name,
        subtitle: email && email !== name ? email : "",
        href: `#/contacts/${contact.ID}`
      }
    );
  };
  function ContactsView({ ctx }) {
    const { __ } = ctx.i18n;
    const [query, setQuery] = d2("");
    const search = useDebounced(query.trim());
    const { data, error, loading } = useFetch(() => ctx.api.get("contacts", {
      search: search || void 0,
      limit: 25,
      orderby: "ID",
      order: "DESC"
    }), [search]);
    const items = data && data.items || [];
    return /* @__PURE__ */ u3(M, { children: [
      /* @__PURE__ */ u3("div", { class: "ghm-search", children: /* @__PURE__ */ u3(Input, { type: "search", placeholder: __("Search contacts"), value: query, onInput: (e3) => setQuery(e3.currentTarget.value), "aria-label": __("Search contacts"), enterkeyhint: "search" }) }),
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      /* @__PURE__ */ u3(Card, { title: search ? __("Results") : __("Recent"), children: [
        loading && !items.length ? /* @__PURE__ */ u3(Loading, {}) : null,
        !loading && !error && !items.length ? /* @__PURE__ */ u3(Empty, { children: search ? __("No contacts found.") : __("No contacts yet.") }) : null,
        items.length ? /* @__PURE__ */ u3(List, { children: items.map((c3) => /* @__PURE__ */ u3(ContactRow, { contact: c3 }, c3.ID)) }) : null
      ] })
    ] });
  }
  function ContactActions({ contact, ctx }) {
    const { registry, loader, ctxFor } = useRuntime();
    const { __ } = ctx.i18n;
    const all = registry.contributions("contact_actions");
    A2(() => {
      all.filter((a3) => !a3.href).forEach((a3) => loader.ensure(a3.feature));
    }, []);
    const actions = all.map((a3) => ({ ...a3, link: a3.href ? resolveHref(a3.href, contact) : null })).filter((a3) => a3.href ? a3.link : true);
    const run = (a3) => {
      const handler = registry.implementation(a3.feature, "contact_actions", a3.id);
      handler ? handler({ contact, ctx: ctxFor(a3.feature) }) : ctx.toast(__("That is not available right now."), "error");
    };
    const render = (a3) => a3.link ? /* @__PURE__ */ u3("a", { class: "ghm-action", href: a3.link, children: [
      /* @__PURE__ */ u3(Icon, { name: a3.icon }),
      a3.label
    ] }) : /* @__PURE__ */ u3("button", { type: "button", class: "ghm-action", onClick: () => run(a3), children: [
      /* @__PURE__ */ u3(Icon, { name: a3.icon }),
      a3.label
    ] });
    const shown = actions.slice(0, 4);
    const rest = actions.slice(4);
    const openRest = () => ctx.sheet(({ close }) => /* @__PURE__ */ u3(List, { children: rest.map((a3) => /* @__PURE__ */ u3(ListItem, { leading: /* @__PURE__ */ u3(Icon, { name: a3.icon }), title: a3.label, href: a3.link || void 0, onClick: a3.link ? void 0 : () => {
      close();
      run(a3);
    } })) }), { title: __("Actions") });
    return /* @__PURE__ */ u3("div", { class: "ghm-actions", children: [
      shown.map(render),
      rest.length ? /* @__PURE__ */ u3("button", { type: "button", class: "ghm-action", onClick: openRest, children: [
        /* @__PURE__ */ u3(Icon, { name: "more" }),
        __("More")
      ] }) : null
    ] });
  }
  function ContactView({ ctx, params }) {
    const { __ } = ctx.i18n;
    const { registry } = useRuntime();
    const { data, error, loading } = useFetch(() => ctx.api.get(`contacts/${params.id}`), [params.id]);
    const contact = data && itemOf(data);
    if (loading && !contact) {
      return /* @__PURE__ */ u3(Loading, {});
    }
    if (error || !contact) {
      return /* @__PURE__ */ u3(ErrorMessage, { error: error || { message: __("Contact not found.") } });
    }
    const name = contactName(contact);
    const details = [
      [__("Email"), contact.data.email, `mailto:${contact.data.email}`],
      [__("Mobile"), contactValue(contact, "mobile_phone"), resolveHref("tel:{mobile_phone}", contact)],
      [__("Phone"), contactValue(contact, "primary_phone"), resolveHref("tel:{primary_phone}", contact)]
    ].filter(([, value]) => value);
    const tags = (contact.tags || []).map((t3) => t3.data.tag_name);
    return /* @__PURE__ */ u3(M, { children: [
      /* @__PURE__ */ u3(Card, { children: [
        /* @__PURE__ */ u3("div", { class: "ghm-contact-head", children: [
          /* @__PURE__ */ u3(Avatar, { large: true, name, src: avatarUrl(contact) }),
          /* @__PURE__ */ u3("h2", { children: name }),
          name !== contact.data.email ? /* @__PURE__ */ u3("p", { children: contact.data.email }) : null
        ] }),
        /* @__PURE__ */ u3(ContactActions, { contact, ctx }),
        tags.length ? /* @__PURE__ */ u3("div", { class: "ghm-tags", children: tags.map((t3) => /* @__PURE__ */ u3(Pill, { children: t3 }, t3)) }) : null
      ] }),
      details.length ? /* @__PURE__ */ u3(Card, { title: __("Details"), children: /* @__PURE__ */ u3(List, { children: details.map(([label, value, href]) => /* @__PURE__ */ u3(ListItem, { title: value, subtitle: label, href: href || void 0 })) }) }) : null,
      registry.contributions("contact_panels").map((panel) => /* @__PURE__ */ u3(Card, { title: panel.label, children: /* @__PURE__ */ u3(Contribution, { point: "contact_panels", feature: panel.feature, id: panel.id, label: panel.label, contact }) }, `${panel.feature}/${panel.id}`))
    ] });
  }

  // src/views/tasks.jsx
  function DuePill({ task, ctx }) {
    const { __, sprintf: sprintf2 } = ctx.i18n;
    if (task.is_complete) {
      return /* @__PURE__ */ u3(Pill, { color: "green", children: __("Done") });
    }
    if (task.is_overdue) {
      return /* @__PURE__ */ u3(Pill, { color: "red", children: __("Overdue") });
    }
    if (task.is_due_today) {
      return /* @__PURE__ */ u3(Pill, { color: "amber", children: __("Today") });
    }
    return /* @__PURE__ */ u3(Pill, { children: sprintf2(__("In %s"), task.i18n.due_in) });
  }
  var CompleteButton = ({ done, onClick, label }) => /* @__PURE__ */ u3("button", { type: "button", class: `ghm-check ${done ? "is-done" : ""}`, "aria-label": label, "aria-pressed": done, onClick, children: done ? /* @__PURE__ */ u3(Icon, { name: "check" }) : null });
  function TaskRow({ task, ctx, onComplete, showContact }) {
    const text = plainText(task.data.summary) || plainText(task.data.content) || ctx.i18n.__("Task");
    const associated = task.associated || {};
    const link = showContact && associated.type === "contact" && (associated.id || task.data.object_id) ? `#/contacts/${associated.id || task.data.object_id}` : void 0;
    return /* @__PURE__ */ u3(
      ListItem,
      {
        control: /* @__PURE__ */ u3(CompleteButton, { done: task.is_complete, label: ctx.i18n.__("Mark complete"), onClick: () => onComplete(task) }),
        title: text,
        subtitle: showContact && associated.name ? associated.name : "",
        meta: /* @__PURE__ */ u3(DuePill, { task, ctx }),
        href: link
      }
    );
  }
  function useTaskList(ctx, query, deps) {
    const { data, error, loading, reload } = useFetch(() => ctx.api.get("tasks", query), deps);
    const [done, setDone] = d2({});
    const complete = (task) => {
      setDone((d3) => ({ ...d3, [task.ID]: true }));
      ctx.api.patch(`tasks/${task.ID}/complete`).then(
        () => ctx.toast(ctx.i18n.__("Task completed")),
        (e3) => {
          setDone((d3) => ({ ...d3, [task.ID]: false }));
          ctx.toast(e3.message, "error");
        }
      );
    };
    const tasks = (data && data.items || []).filter((t3) => !done[t3.ID]);
    return { tasks, error, loading, reload, complete };
  }
  var myTasksQuery = (ctx) => ({
    user_id: ctx.user.id,
    incomplete: true,
    orderby: "due_date",
    order: "ASC",
    limit: 50
  });
  function TasksView({ ctx }) {
    const { __ } = ctx.i18n;
    const { tasks, error, loading, complete } = useTaskList(ctx, myTasksQuery(ctx), []);
    const groups = [
      [__("Overdue"), tasks.filter((t3) => t3.is_overdue)],
      [__("Today"), tasks.filter((t3) => !t3.is_overdue && t3.is_due_today)],
      [__("Upcoming"), tasks.filter((t3) => !t3.is_overdue && !t3.is_due_today)]
    ].filter(([, items]) => items.length);
    return /* @__PURE__ */ u3(M, { children: [
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      loading && !tasks.length ? /* @__PURE__ */ u3(Loading, {}) : null,
      !loading && !error && !tasks.length ? /* @__PURE__ */ u3(Card, { children: /* @__PURE__ */ u3(Empty, { children: __("Nothing to do. Enjoy it.") }) }) : null,
      groups.map(([title, items]) => /* @__PURE__ */ u3(Card, { title, children: /* @__PURE__ */ u3(List, { children: items.map((t3) => /* @__PURE__ */ u3(TaskRow, { task: t3, ctx, onComplete: complete, showContact: true }, t3.ID)) }) }, title))
    ] });
  }
  function NewTaskSheet({ ctx, close, contact, onCreated }) {
    const { __ } = ctx.i18n;
    const [content, setContent] = d2("");
    const [due, setDue] = d2(tomorrowMorning());
    const [saving, setSaving] = d2(false);
    const save = async (e3) => {
      e3.preventDefault();
      if (!content.trim()) {
        return;
      }
      setSaving(true);
      const data = {
        content: content.trim(),
        type: "task",
        user_id: ctx.user.id,
        due_date: toApiDate(due)
      };
      if (contact) {
        data.object_id = contact.ID;
        data.object_type = "contact";
      }
      try {
        await ctx.api.post("tasks", { data });
        ctx.toast(__("Task added"));
        onCreated && onCreated();
        close();
      } catch (err) {
        setSaving(false);
        ctx.toast(err.message, "error");
      }
    };
    return /* @__PURE__ */ u3("form", { onSubmit: save, children: [
      /* @__PURE__ */ u3(Field, { label: __("What needs doing?"), children: /* @__PURE__ */ u3(Textarea, { value: content, onInput: (e3) => setContent(e3.currentTarget.value), required: true, autofocus: true }) }),
      /* @__PURE__ */ u3(Field, { label: __("Due"), children: /* @__PURE__ */ u3(Input, { type: "datetime-local", value: due, onInput: (e3) => setDue(e3.currentTarget.value) }) }),
      /* @__PURE__ */ u3(Button, { variant: "primary", block: true, type: "submit", disabled: saving, children: __("Add task") })
    ] });
  }

  // src/views/home.jsx
  function HomeView({ ctx }) {
    const { registry } = useRuntime();
    const cards = registry.contributions("home_cards");
    return /* @__PURE__ */ u3(M, { children: cards.map((card) => /* @__PURE__ */ u3(Contribution, { point: "home_cards", feature: card.feature, id: card.id, label: card.label }, `${card.feature}/${card.id}`)) });
  }
  function MyTasksCard({ ctx }) {
    const { __ } = ctx.i18n;
    const { tasks, error, loading, complete } = useTaskList(ctx, { ...myTasksQuery(ctx), limit: 5 }, []);
    const seeAll = /* @__PURE__ */ u3("a", { href: "#/tasks", style: "color:var(--ghm-accent);text-transform:none;letter-spacing:0", children: __("See all") });
    return /* @__PURE__ */ u3(Card, { title: __("My tasks"), action: seeAll, children: [
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      loading && !tasks.length ? /* @__PURE__ */ u3(Loading, {}) : null,
      !loading && !error && !tasks.length ? /* @__PURE__ */ u3(Empty, { children: __("Nothing to do. Enjoy it.") }) : null,
      tasks.length ? /* @__PURE__ */ u3(List, { children: tasks.map((t3) => /* @__PURE__ */ u3(TaskRow, { task: t3, ctx, onComplete: complete, showContact: true }, t3.ID)) }) : null
    ] });
  }
  function RecentContactsCard({ ctx }) {
    const { __ } = ctx.i18n;
    const { data, error, loading } = useFetch(() => ctx.api.get("contacts", { limit: 5, orderby: "ID", order: "DESC" }), []);
    const items = data && data.items || [];
    const seeAll = /* @__PURE__ */ u3("a", { href: "#/contacts", style: "color:var(--ghm-accent);text-transform:none;letter-spacing:0", children: __("See all") });
    if (!loading && !error && !items.length) {
      return null;
    }
    return /* @__PURE__ */ u3(Card, { title: __("Recently added"), action: seeAll, children: [
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      loading && !items.length ? /* @__PURE__ */ u3(Loading, {}) : null,
      items.length ? /* @__PURE__ */ u3(List, { children: items.map((c3) => /* @__PURE__ */ u3(ContactRow, { contact: c3 }, c3.ID)) }) : null
    ] });
  }

  // src/views/panels.jsx
  function NotesPanel({ ctx, contact }) {
    const { __ } = ctx.i18n;
    const [text, setText] = d2("");
    const [saving, setSaving] = d2(false);
    const { data, error, loading, reload } = useFetch(() => ctx.api.get("notes", {
      object_id: contact.ID,
      object_type: "contact",
      orderby: "timestamp",
      order: "DESC",
      limit: 20
    }), [contact.ID]);
    const notes = data && data.items || [];
    const add = async (e3) => {
      e3.preventDefault();
      if (!text.trim()) {
        return;
      }
      setSaving(true);
      try {
        await ctx.api.post("notes", { data: { object_id: contact.ID, object_type: "contact", content: text.trim(), type: "note", context: "user" } });
        setText("");
        ctx.toast(__("Note added"));
        await reload();
      } catch (err) {
        ctx.toast(err.message, "error");
      }
      setSaving(false);
    };
    return /* @__PURE__ */ u3(M, { children: [
      ctx.can("add_notes") ? /* @__PURE__ */ u3("form", { class: "ghm-note-form", onSubmit: add, children: [
        /* @__PURE__ */ u3(Textarea, { placeholder: __("Add a note"), "aria-label": __("Add a note"), value: text, onInput: (e3) => setText(e3.currentTarget.value) }),
        /* @__PURE__ */ u3(Button, { variant: "primary", type: "submit", disabled: saving || !text.trim(), "aria-label": __("Save note"), children: /* @__PURE__ */ u3(Icon, { name: "check" }) })
      ] }) : null,
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      loading && !notes.length ? /* @__PURE__ */ u3(Loading, {}) : null,
      !loading && !error && !notes.length ? /* @__PURE__ */ u3(Empty, { children: __("No notes yet.") }) : null,
      notes.map((n2) => /* @__PURE__ */ u3("div", { class: "ghm-note", children: [
        /* @__PURE__ */ u3("p", { children: plainText(n2.data.content) }),
        /* @__PURE__ */ u3("small", { children: [
          n2.i18n.time_diff,
          " ",
          __("ago")
        ] })
      ] }, n2.ID))
    ] });
  }
  function ContactTasksPanel({ ctx, contact }) {
    const { __ } = ctx.i18n;
    const { tasks, error, loading, reload, complete } = useTaskList(ctx, {
      object_id: contact.ID,
      object_type: "contact",
      incomplete: true,
      orderby: "due_date",
      order: "ASC",
      limit: 20
    }, [contact.ID]);
    return /* @__PURE__ */ u3(M, { children: [
      /* @__PURE__ */ u3(ErrorMessage, { error }),
      loading && !tasks.length ? /* @__PURE__ */ u3(Loading, {}) : null,
      !loading && !error && !tasks.length ? /* @__PURE__ */ u3(Empty, { children: __("No open tasks.") }) : null,
      tasks.length ? /* @__PURE__ */ u3(List, { children: tasks.map((t3) => /* @__PURE__ */ u3(TaskRow, { task: t3, ctx, onComplete: complete }, t3.ID)) }) : null,
      ctx.can("add_tasks") ? /* @__PURE__ */ u3("div", { style: "padding:12px 16px", children: /* @__PURE__ */ u3(Button, { block: true, icon: "plus", onClick: () => ctx.sheet(NewTaskSheet, { title: __("New task"), contact, onCreated: reload }), children: __("Add a task") }) }) : null
    ] });
  }
  function NewContactSheet({ ctx, close }) {
    const { __ } = ctx.i18n;
    const [f3, setF] = d2({ first_name: "", last_name: "", email: "", mobile_phone: "" });
    const [saving, setSaving] = d2(false);
    const set = (key) => (e3) => setF((v3) => ({ ...v3, [key]: e3.currentTarget.value }));
    const save = async (e3) => {
      e3.preventDefault();
      setSaving(true);
      try {
        const res = await ctx.api.post("contacts", {
          data: { first_name: f3.first_name.trim(), last_name: f3.last_name.trim(), email: f3.email.trim(), owner_id: ctx.user.id },
          meta: f3.mobile_phone.trim() ? { mobile_phone: f3.mobile_phone.trim() } : {}
        });
        const contact = itemOf(res);
        ctx.toast(__("Contact added"));
        close();
        if (contact) {
          ctx.navigate(`/contacts/${contact.ID}`);
        }
      } catch (err) {
        setSaving(false);
        ctx.toast(err.message, "error");
      }
    };
    return /* @__PURE__ */ u3("form", { onSubmit: save, children: [
      /* @__PURE__ */ u3(Field, { label: __("First name"), children: /* @__PURE__ */ u3(Input, { value: f3.first_name, onInput: set("first_name"), autocomplete: "off", autofocus: true }) }),
      /* @__PURE__ */ u3(Field, { label: __("Last name"), children: /* @__PURE__ */ u3(Input, { value: f3.last_name, onInput: set("last_name"), autocomplete: "off" }) }),
      /* @__PURE__ */ u3(Field, { label: __("Email"), children: /* @__PURE__ */ u3(Input, { type: "email", value: f3.email, onInput: set("email"), required: true, autocomplete: "off" }) }),
      /* @__PURE__ */ u3(Field, { label: __("Mobile phone"), children: /* @__PURE__ */ u3(Input, { type: "tel", value: f3.mobile_phone, onInput: set("mobile_phone"), autocomplete: "off" }) }),
      /* @__PURE__ */ u3(Button, { variant: "primary", block: true, type: "submit", disabled: saving, children: __("Add contact") })
    ] });
  }

  // src/features/core.jsx
  function registerCore() {
    return window.GroundhoggMobile.register("core", {
      apiVersion: 1,
      routes: {
        "/": HomeView,
        "/contacts": ContactsView,
        "/contacts/:id": { component: ContactView, title: "Contact" },
        "/tasks": TasksView
      },
      homeCards: {
        "my-tasks": MyTasksCard,
        "recent-contacts": RecentContactsCard
      },
      contactPanels: {
        notes: NotesPanel,
        tasks: ContactTasksPanel
      },
      quickCreate: {
        "new-contact": NewContactSheet,
        "new-task": NewTaskSheet
      }
    });
  }

  // src/app.jsx
  var TAB_SLOTS = 5;
  function useHashRoute() {
    const [route, setRoute] = d2(() => parseHash(window.location.hash));
    A2(() => {
      const onChange = () => {
        setRoute(parseHash(window.location.hash));
        window.scrollTo(0, 0);
      };
      window.addEventListener("hashchange", onChange);
      return () => window.removeEventListener("hashchange", onChange);
    }, []);
    return route;
  }
  function layoutNav(items, hasCreate) {
    const requested = items.filter((i3) => i3.placement === "tab");
    const slots = TAB_SLOTS - 1 - (hasCreate ? 1 : 0);
    return {
      tabs: requested.slice(0, slots),
      more: [...requested.slice(slots), ...items.filter((i3) => i3.placement !== "tab")]
    };
  }
  function isActive(item, path) {
    const own = normalizePath(item.route);
    return own === "/" ? path === "/" : firstSegment(own) === firstSegment(path);
  }
  function TabBar({ tabs, hasCreate, hasMore, path, onCreate }) {
    const { ctxFor } = useRuntime();
    const { __ } = ctxFor("core").i18n;
    const items = tabs.map((item) => /* @__PURE__ */ u3("a", { class: `ghm-tab ${isActive(item, path) ? "is-active" : ""}`, href: toHash(item.route), "aria-current": isActive(item, path) ? "page" : void 0, children: [
      /* @__PURE__ */ u3(Icon, { name: item.icon }),
      item.label
    ] }));
    if (hasCreate) {
      const create = /* @__PURE__ */ u3("button", { type: "button", class: "ghm-tab ghm-tab-create", onClick: onCreate, "aria-label": __("Create"), children: /* @__PURE__ */ u3("span", { children: /* @__PURE__ */ u3(Icon, { name: "plus" }) }) });
      items.splice(Math.ceil(items.length / 2), 0, create);
    }
    if (hasMore) {
      items.push(
        /* @__PURE__ */ u3("a", { class: `ghm-tab ${path === "/more" ? "is-active" : ""}`, href: "#/more", children: [
          /* @__PURE__ */ u3(Icon, { name: "menu" }),
          __("More")
        ] })
      );
    }
    return /* @__PURE__ */ u3("nav", { class: "ghm-tabs", "aria-label": __("Main"), children: items });
  }
  function MoreView({ moreItems }) {
    const { manifest, ctxFor } = useRuntime();
    const { __ } = ctxFor("core").i18n;
    return /* @__PURE__ */ u3(M, { children: [
      moreItems.length ? /* @__PURE__ */ u3("div", { class: "ghm-card", children: /* @__PURE__ */ u3(List, { children: moreItems.map((item) => /* @__PURE__ */ u3(ListItem, { leading: /* @__PURE__ */ u3(Icon, { name: item.icon }), title: item.label, href: toHash(item.route), trailing: /* @__PURE__ */ u3(Icon, { name: "chevron" }) })) }) }) : null,
      /* @__PURE__ */ u3("div", { class: "ghm-card", children: /* @__PURE__ */ u3(List, { children: [
        /* @__PURE__ */ u3(ListItem, { leading: /* @__PURE__ */ u3(Icon, { name: "external" }), title: __("Open the full site"), subtitle: __("Settings, flows and the email editor live on desktop."), href: manifest.app.adminUrl }),
        /* @__PURE__ */ u3(ListItem, { leading: /* @__PURE__ */ u3(Icon, { name: "logout" }), title: __("Log out"), subtitle: manifest.user.name, href: manifest.app.logoutUrl })
      ] }) })
    ] });
  }
  function App({ runtime }) {
    const route = useHashRoute();
    const { registry, loader, ctxFor } = runtime;
    const { __ } = ctxFor("core").i18n;
    const [, bump] = _2((n2) => n2 + 1, 0);
    A2(() => registry.subscribe(bump), [registry]);
    const [sheet, setSheet] = d2(null);
    const [toast, setToast] = d2(null);
    const [failed, setFailed] = d2({});
    A2(() => {
      let timer;
      runtime.shell.toast = (message, type) => {
        setToast({ message, type });
        clearTimeout(timer);
        timer = setTimeout(() => setToast(null), 2800);
      };
      runtime.shell.sheet = (Component, props) => setSheet({ Component, props });
      return () => clearTimeout(timer);
    }, []);
    const closeSheet = j2(() => setSheet(null), []);
    const nav = registry.contributions("nav");
    const creates = registry.contributions("quick_create");
    const { tabs, more } = layoutNav(nav, creates.length > 0);
    const match = matchRoute(registry.routes(), route.path);
    const owner = match ? null : registry.featureForPath(route.path);
    A2(() => {
      if (!match && owner && !failed[owner]) {
        loader.ensure(owner).then((ok) => ok || setFailed((f3) => ({ ...f3, [owner]: true })));
      }
    }, [route.path, owner, !!match]);
    const atRoot = nav.some((i3) => normalizePath(i3.route) === route.path) || route.path === "/more";
    const navItem = nav.find((i3) => normalizePath(i3.route) === route.path);
    const title = match && match.route.title || navItem && navItem.label || (route.path === "/more" ? __("More") : runtime.manifest.app.name);
    const goBack = () => {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.hash = "#/";
      }
    };
    const openCreate = () => setSheet({
      Component: CreateMenu,
      props: { title: __("Create"), items: creates }
    });
    let content;
    if (match) {
      const Page = match.route.component;
      content = /* @__PURE__ */ u3(ErrorBoundary, { label: title, children: /* @__PURE__ */ u3(Page, { ctx: ctxFor(match.route.feature), params: match.params, query: route.query }) }, route.path);
    } else if (route.path === "/more") {
      content = /* @__PURE__ */ u3(MoreView, { moreItems: more });
    } else if (owner && !failed[owner]) {
      content = /* @__PURE__ */ u3(Loading, {});
    } else {
      content = /* @__PURE__ */ u3(Empty, { children: [
        __("There is nothing here."),
        " ",
        /* @__PURE__ */ u3("a", { href: "#/", style: "color:var(--ghm-accent)", children: __("Go home") })
      ] });
    }
    return /* @__PURE__ */ u3(RuntimeContext.Provider, { value: runtime, children: /* @__PURE__ */ u3("div", { class: "ghm-app", children: [
      /* @__PURE__ */ u3("header", { class: "ghm-head", children: [
        /* @__PURE__ */ u3("div", { class: "ghm-head-side", children: atRoot ? null : /* @__PURE__ */ u3(Button, { class: "icon", icon: "back", "aria-label": __("Back"), onClick: goBack }) }),
        /* @__PURE__ */ u3("h1", { children: title }),
        /* @__PURE__ */ u3("div", { class: "ghm-head-side" })
      ] }),
      /* @__PURE__ */ u3("main", { class: "ghm-main", children: content }),
      /* @__PURE__ */ u3(TabBar, { tabs, hasCreate: creates.length > 0, hasMore: true, path: route.path, onCreate: openCreate }),
      sheet ? /* @__PURE__ */ u3(Sheet, { title: sheet.props && sheet.props.title, onClose: closeSheet, children: /* @__PURE__ */ u3(ErrorBoundary, { label: __("Sheet"), children: /* @__PURE__ */ u3(sheet.Component, { ...sheet.props, close: closeSheet, ctx: ctxFor(sheet.props && sheet.props.feature || "core") }) }) }) : null,
      toast ? /* @__PURE__ */ u3(Toast, { ...toast }) : null
    ] }) });
  }
  function CreateMenu({ items, close, ctx }) {
    const { registry, loader } = useRuntime();
    const [busy, setBusy] = d2("");
    const pick = async (item) => {
      setBusy(item.id);
      await loader.ensure(item.feature);
      const Impl = registry.implementation(item.feature, "quick_create", item.id);
      if (!Impl) {
        setBusy("");
        ctx.toast(ctx.i18n.__("That is not available right now."), "error");
        return;
      }
      ctx.sheet(Impl, { title: item.label, feature: item.feature });
    };
    return /* @__PURE__ */ u3(List, { children: items.map((item) => /* @__PURE__ */ u3(ListItem, { leading: /* @__PURE__ */ u3(Icon, { name: item.icon }), title: item.label, onClick: () => pick(item), trailing: busy === item.id ? /* @__PURE__ */ u3(Loading, {}) : null })) });
  }

  // src/index.jsx
  function boot() {
    const el = document.getElementById("ghm-manifest");
    const root = document.getElementById("ghm-root");
    if (!el || !root) {
      return;
    }
    const manifest = JSON.parse(el.textContent);
    const runtime = createRuntime(manifest);
    registerCore();
    G(/* @__PURE__ */ u3(App, { runtime }), root);
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register(manifest.app.url + "sw.js", { scope: manifest.app.url }).catch(() => {
      });
    }
  }
  boot();
})();
