@component('mail::message')
# 📩 New Contact Us Message

You have received a new message from the contact form.

---

**👤 Name:**  
{{ $data['name'] }}

**📧 Email:**  
{{ $data['email'] }}

@if(!empty($data['phone']))
**📞 Phone:**  
{{ $data['phone'] }}
@endif

---

**💬 Message:**  

@component('mail::panel')
{{ $data['message'] }}
@endcomponent

---

Thanks,  
{{ config('app.name') }}
@endcomponent