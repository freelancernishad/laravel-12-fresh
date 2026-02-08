<!-- Global Code Example Modal -->
<div id="code-modal" class="fixed inset-0 z-[70] hidden overflow-y-auto">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md" onclick="closeCodeModal()"></div>
        <div class="relative w-full max-w-3xl">
            <div class="glass rounded-[2rem] shadow-2xl overflow-hidden border border-white/10">
                <!-- Modal Header -->
                <div class="p-6 border-b border-white/5 bg-white/5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            Code Integration Details
                        </h3>
                        <p id="code-modal-url" class="text-xs text-slate-500 font-mono mt-1"></p>
                    </div>
                    <button onclick="closeCodeModal()" class="p-2 rounded-xl bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Prism Theme -->
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
                    <style>
                        /* Custom Prism Overrides */
                        pre[class*="language-"] {
                            background: rgba(0, 0, 0, 0.6) !important;
                            border: 1px solid rgba(255, 255, 255, 0.05) !important;
                            border-radius: 1rem !important;
                            margin: 0 !important;
                            padding: 1.5rem !important;
                            min-height: 300px;
                        }
                        code[class*="language-"] {
                            text-shadow: none !important;
                            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
                        }
                    </style>

                    <!-- Language Switcher -->
                    <div class="flex flex-wrap p-1 bg-black/40 rounded-2xl w-fit border border-white/5 gap-1">
                        <button onclick="switchLanguage('docs')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="docs">Integration</button>
                        <button onclick="switchLanguage('js')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="js">JavaScript</button>
                        <button onclick="switchLanguage('react')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="react">React / Next.js</button>
                        <button onclick="switchLanguage('rtk')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="rtk">RTK Query</button>
                        <button onclick="switchLanguage('php')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="php">PHP (cURL)</button>
                        <button onclick="switchLanguage('curl')" class="lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all" data-lang="curl">cURL</button>
                    </div>

                    <!-- Code Display -->
                    <div class="relative group">
                        <button onclick="copyCodeContent(this)" class="absolute top-4 right-4 p-2.5 rounded-xl bg-white/5 text-slate-500 hover:text-white transition-all border border-white/10 z-10 backdrop-blur-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 icon-copy" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500 hidden icon-check" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <pre id="code-pre" class="language-javascript"><code id="code-display" class="language-javascript"></code></pre>
                    </div>

                    <!-- Usage Example (Initially Hidden) -->
                    <div id="usage-section" class="hidden space-y-4">
                        <div class="flex items-center gap-2 text-indigo-400">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-widest">Component Usage Example</span>
                        </div>
                        <div class="relative group">
                            <button onclick="copyCodeContent(this, 'usage-display')" class="absolute top-4 right-4 p-2.5 rounded-xl bg-white/5 text-slate-500 hover:text-white transition-all border border-white/10 z-10 backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 icon-copy" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500 hidden icon-check" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <pre id="usage-pre" class="language-javascript"><code id="usage-display" class="language-javascript"></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCodeData = { method: '', url: '', body: null };

    function showCodeExample(method, url, body = null) {
        currentCodeData = { method, url, body };
        document.getElementById('code-modal-url').innerText = `${method} ${url}`;
        switchLanguage('docs');
        document.getElementById('code-modal').classList.remove('hidden');
    }

    function showCodeExampleFromModal() {
        const method = document.getElementById('doc-method').innerText;
        const url = document.getElementById('doc-endpoint').innerText;
        const body = document.getElementById('doc-body').innerText;
        showCodeExample(method, url, body);
    }

    function closeCodeModal() {
        document.getElementById('code-modal').classList.add('hidden');
    }

    function switchLanguage(lang) {
        // Update Buttons
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.className = 'lang-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-white transition-all';
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.remove('text-slate-500');
                btn.classList.add('text-white', 'bg-indigo-500');
            }
        });

        const { method, url, body } = currentCodeData;
        let code = '';
        let prismLang = 'javascript';

        // Update Language Classes
        const codeElement = document.getElementById('code-display');
        const preElement = document.getElementById('code-pre');
        
        if (lang === 'php') prismLang = 'php';
        else if (lang === 'curl') prismLang = 'bash';
        else if (lang === 'react' || lang === 'rtk') prismLang = 'jsx';
        else if (lang === 'docs') prismLang = 'plaintext';

        preElement.className = `language-${prismLang}`;
        codeElement.className = `language-${prismLang}`;

        const usageSection = document.getElementById('usage-section');
        const usageDisplay = document.getElementById('usage-display');
        const usagePre = document.getElementById('usage-pre');
        usageSection.classList.add('hidden');
        let usageCode = '';
        
        const headers = {
            'Authorization': 'Bearer YOUR_TOKEN',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        const bodyStr = body ? (typeof body === 'string' ? body : JSON.stringify(body, null, 2)) : '';
        const bodyCompact = body ? (typeof body === 'string' ? body : JSON.stringify(body)) : '';

        if (lang === 'docs') {
            const endpointName = url.split('/').pop().replace(/-/g, ' ');
            const capitalizedEndpoint = endpointName.charAt(0).toUpperCase() + endpointName.slice(1);
            
            code = `INTEGRATION GUIDE: ${method} ${capitalizedEndpoint}

1. OVERVIEW
   This endpoint allows you to ${method === 'GET' ? 'fetch' : (method === 'POST' ? 'create' : 'update')} ${endpointName} data programmatically.

2. AUTHENTICATION
   Requirement: Bearer Token
   Header: 'Authorization: Bearer YOUR_TOKEN'
   
   * Obtain your token from the Admin > Security settings.
   * Ensure your domain is whitelisted in "Allowed Origins".

3. REQUEST DETAILS
   Endpoint: ${url}
   Method: ${method}
   Headers: 
     - Accept: application/json
     - Content-Type: application/json
${body ? `\n   Request Body (JSON):\n${bodyStr}\n` : ''}
4. INTEGRATION STEPS
   a. Set up your HTTP client with the base URL and authentication headers.
   b. ${method !== 'GET' ? 'Construct the JSON payload matching the schema above.' : 'Send a GET request to the specified endpoint.'}
   c. Parse the JSON response. A success usually returns a 200 or 201 status code.
   d. Handle 401 (Unauthorized) or 403 (Forbidden) by checking your token and CORS settings.

5. SAMPLE RESPONSE HANDLING
   Success: { "status": "success", "data": { ... } }
   Error: { "status": "error", "message": "Reason for failure" }`;
        } else if (lang === 'js') {
            const fetchOptions = {
                method: method,
                headers: headers
            };
            if (body) fetchOptions.body = 'BODY_PLACEHOLDER';
            
            code = `fetch('${url}', ${JSON.stringify(fetchOptions, null, 2)})`
                .replace('"BODY_PLACEHOLDER"', bodyStr) + 
                `\n.then(response => response.json())\n.then(data => console.log(data))\n.catch(error => console.error('Error:', error));`;
        } else if (lang === 'rtk') {
            const endpointBase = url.split('/').pop().replace(/-/g, '');
            const endpointName = method.toLowerCase() + endpointBase.charAt(0).toUpperCase() + endpointBase.slice(1);
            const isMutation = method !== 'GET';
            const relativeUrl = url.replace(window.location.origin + '/api/', '');
            
            code = `// 1. API Slice Definition (@reduxjs/toolkit/query/react)
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const api = createApi({
  reducerPath: 'api',
  baseQuery: fetchBaseQuery({ 
    baseUrl: '${window.location.origin}/api/',
    prepareHeaders: (headers) => {
      headers.set('Auth' + 'orization', 'Bearer YOUR_TOKEN');
      headers.set('Accept', 'application/json');
      return headers;
    },
  }),
  endpoints: (builder) => ({
    ${endpointName}: builder.${isMutation ? 'mutation' : 'query'}({
      query: (${body ? 'data' : ''}) => ({
        url: '${relativeUrl}',
        method: '${method}',
        ${body ? 'body: data' : ''}
      }),
    }),
  }),
});

export const { use${endpointName.charAt(0).toUpperCase() + endpointName.slice(1)}${isMutation ? 'Mutation' : 'Query'} } = api;`;

            usageCode = `// 2. Component Usage Example
function MyComponent() {
  // Hook usage
  const [${endpointName}, { isLoading, error }] = use${endpointName.charAt(0).toUpperCase() + endpointName.slice(1)}${isMutation ? 'Mutation' : 'Query'}();

  const handleExecute = async () => {
    const payload = ${bodyStr || '{}'};
    try {
      const response = await ${endpointName}(payload).unwrap();
      console.log('Success:', response);
    } catch (err) {
      console.error('Failed:', err);
    }
  };

  return (
    <button onClick={handleExecute} disabled={isLoading}>
      {isLoading ? 'Processing...' : 'Run ${method}'}
    </button>
  );
}`;
            usageSection.classList.remove('hidden');
        } else if (lang === 'react') {
            const isGet = method === 'GET';
            const endpointName = url.split('/').pop().replace(/-/g, ' ');
            const componentName = endpointName.charAt(0).toUpperCase() + endpointName.slice(1).replace(/\s+/g, '') + 'Manager';
            const stateName = endpointName.replace(/\s+/g, '');
            
            usageCode = `// Implementation Tip:
// You can directly drop this component into your codebase.
// Make sure to replace 'YOUR_TOKEN' with your actual token.`;

            code = `// React / Next.js Component Example
import React, { useState, useEffect } from 'react';

const ${componentName} = () => {
  const [${stateName}, set${stateName.charAt(0).toUpperCase() + stateName.slice(1)}] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const containerStyle = { padding: '20px', border: '1px solid #ccc', borderRadius: '12px' };
  const errorStyle = { color: 'red' };
  const preStyle = { background: '#f4f4f4', padding: '10px' };
  const buttonStyle = { padding: '10px 20px', background: '#4f46e5', color: 'white', border: 'none', borderRadius: '8px' };

  const handleRequest = async (${body ? 'payload' : ''}) => {
    setLoading(true);
    setError(null);
    try {
      const response = await fetch('${url}', {
        method: '${method}',
        headers: {
          ['Auth' + 'orization']: 'Bearer YOUR_TOKEN',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        ${body ? 'body: JSON.stringify(payload)' : ''}
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'API Error');
      set${stateName.charAt(0).toUpperCase() + stateName.slice(1)}(isGet ? (result.data || result) : result);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  ${isGet ? `useEffect(() => {
    handleRequest();
  }, []); // Initial load for GET` : ''}

  return (
    <div style={containerStyle}>
      <h2>${method} ${endpointName.toUpperCase()}</h2>
      
      {loading && <p>Loading...</p>}
      {error && <p style={errorStyle}>Error: {error}</p>}
      
      {${stateName} && (
        <pre style={preStyle}>
          {JSON.stringify(${stateName}, null, 2)}
        </pre>
      )}

      ${!isGet ? `
      <button 
        onClick={() => handleRequest(${bodyStr || '{}'})}
        style={buttonStyle}
      >
        Run Action
      </button>` : ''}
    </div>
  );
};

export default ${componentName};`;

            usageSection.classList.remove('hidden');
        } else if (lang === 'php') {
            let phpBody = '';
            if (body) {
                const escapedBody = bodyCompact.replace(/'/g, "\\'");
                phpBody = `\ncurl_setopt($ch, CURLOPT_POSTFIELDS, '${escapedBody}');`;
            }
            
            code = '<' + '\x3f' + `php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "${url}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "${method}");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_TOKEN',
    'Accept: application/json',
    'Content-Type: application/json'
]);${phpBody}

$response = curl_exec($ch);
curl_close($ch);
echo $response;`;
        } else if (lang === 'curl') {
            const dataRaw = body ? ` \\\n--data-raw '${bodyCompact}'` : '';
            code = `curl --location --request ${method} '${url}' \\
--header 'Authorization: Bearer YOUR_TOKEN' \\
--header 'Accept: application/json' \\
--header 'Content-Type: application/json'${dataRaw}`;
        }

        // Apply Usage Example if active
        if (!usageSection.classList.contains('hidden')) {
            usagePre.className = `language-${prismLang}`;
            usageDisplay.className = `language-${prismLang}`;
            usageDisplay.textContent = usageCode;
            if (window.Prism) Prism.highlightElement(usageDisplay);
        }

        const display = document.getElementById('code-display');
        display.textContent = code;
        if (window.Prism) {
            Prism.highlightElement(display);
        }
    }

    function copyCodeContent(btn, elementId = 'code-display') {
        const content = document.getElementById(elementId).innerText;
        copyToClipboard(content, btn);
    }

    function copyToClipboard(text, btn = null) {
        const copyAction = () => {
            showToast('Copied to clipboard!');
            if (btn) {
                const copyIcon = btn.querySelector('.icon-copy');
                const checkIcon = btn.querySelector('.icon-check');
                if (copyIcon && checkIcon) {
                    copyIcon.classList.add('hidden');
                    checkIcon.classList.remove('hidden');
                    setTimeout(() => {
                        copyIcon.classList.remove('hidden');
                        checkIcon.classList.add('hidden');
                    }, 2000);
                }
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(copyAction).catch(err => {
                console.error('Clipboard API failed, trying fallback:', err);
                fallbackCopyTextToClipboard(text, copyAction);
            });
        } else {
            fallbackCopyTextToClipboard(text, copyAction);
        }
    }

    function fallbackCopyTextToClipboard(text, callback) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            const successful = document.execCommand('copy');
            if (successful) callback();
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showToast('Failed to copy', 'error');
        }
        document.body.removeChild(textArea);
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
        toast.className = `fixed bottom-8 right-8 px-6 py-3 rounded-xl ${bgColor} text-white font-bold shadow-2xl z-[100] transition-all transform translate-y-20 opacity-0`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        }, 10);
        
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
</script>

<!-- Prism Core -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<!-- Languages -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-jsx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
