{$btn_text = $btn_text|default:__("helpdesk_account.sign_in")}
{$btn_href = $btn_href|default:$app["helpdesk.connect_url"]}
<a class="btn btn-primary {$btn_class}"
   href="{fn_url($btn_href)}"
>
    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#clip0_1439_21472)">
            <path d="M15 5.10759V13.1026C15 14.0624 14.214 14.8401 13.2438 14.8401H5.16263C4.19241 14.8401 3.4064 14.0624 3.4064 13.1026V5.10759C3.4064 4.14771 4.19241 3.37008 5.16263 3.37008H13.2438C14.214 3.37008 15 4.14771 15 5.10759ZM1.75623 0C0.786007 0 0 0.777626 0 1.73751C0 2.69739 0.786007 3.47502 1.75623 3.47502C2.72646 3.47502 3.51247 2.69739 3.51247 1.73751C3.51247 0.777626 2.72646 0 1.75623 0Z" fill="white"/>
        </g>
        <defs>
            <clipPath id="clip0_1439_21472">
                <rect width="15" height="15" fill="white"/>
            </clipPath>
        </defs>
    </svg>
    {$btn_text}
</a>
