import { useMemo, useState } from 'react';
import { AlertTriangle, CheckCircle2, ChevronRight, FileCheck2, FileText, LockKeyhole, ShieldCheck, Upload, X } from 'lucide-react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AppShell } from '../components/layout/AppShell';
import { apiFetch, type ApiError } from '../lib/api';
import '../styles/kyc.css';

type FieldPolicy = { code: string; label: string; required: boolean };
type DocumentPolicy = FieldPolicy;
type KycDocument = { id: string; document_type: string; mime_type: string; size_bytes: number; scan_status: string; review_status: string; uploaded_at: string };
type KycCase = { id: string; status: string; country_code?: string; personal_details?: Record<string, string>; documents?: KycDocument[]; submitted_at?: string; reviewed_at?: string };
type KycResponse = { data: { case: KycCase; policy: { country_code: string; country_name?: string; eligible: boolean; available: boolean; required_fields: FieldPolicy[]; document_types: DocumentPolicy[] } } };

const statusOrder = ['not_started', 'draft', 'submitted', 'under_review', 'additional_information_required', 'approved'];
const labels: Record<string, { en: string; mm: string }> = {
  legal_first_name: { en: 'Legal first name', mm: 'တရားဝင် အမည်' },
  legal_last_name: { en: 'Legal last name', mm: 'တရားဝင် မျိုးနွယ်အမည်' },
  date_of_birth: { en: 'Date of birth', mm: 'မွေးသက္ကရာဇ်' },
  nationality: { en: 'Nationality', mm: 'နိုင်ငံသား' },
  residence_country_code: { en: 'Country of residence', mm: 'နေထိုင်သည့်နိုင်ငံ' },
  address_line1: { en: 'Residential address', mm: 'နေအိမ်လိပ်စာ' },
  address_line2: { en: 'Address line 2', mm: 'လိပ်စာအပိုင်း ၂' },
  city: { en: 'City / town', mm: 'မြို့' },
  postal_code: { en: 'Postal code', mm: 'စာတိုက်ကုဒ်' },
};

function message(error: unknown): string {
  return (error as ApiError)?.message || 'Something went wrong. Please try again.';
}

function statusLabel(status: string, mm: boolean): string {
  const values: Record<string, [string, string]> = { not_started: ['Not started', 'မစတင်ရသေးပါ'], draft: ['Draft', 'မူကြမ်း'], submitted: ['Submitted', 'တင်ပြီး'], under_review: ['Under review', 'စစ်ဆေးနေသည်'], additional_information_required: ['Action required', 'အချက်အလက် ထပ်လိုသည်'], approved: ['Approved', 'အတည်ပြုသည်'], rejected: ['Rejected', 'ပယ်ချသည်'] };
  return values[status]?.[mm ? 1 : 0] || status.replaceAll('_', ' ');
}

export function KycPage() {
  const queryClient = useQueryClient();
  const mm = localStorage.getItem('dw-locale') === 'my';
  const [step, setStep] = useState(1);
  const [details, setDetails] = useState<Record<string, string>>({});
  const [documentType, setDocumentType] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [consent, setConsent] = useState(false);
  const [notice, setNotice] = useState('');
  const [error, setError] = useState('');
  const kyc = useQuery({ queryKey: ['customer-kyc'], queryFn: () => apiFetch<KycResponse>('/kyc') });
  const current = kyc.data?.data.case;
  const policy = kyc.data?.data.policy;
  const values = useMemo(() => ({ ...(current?.personal_details || {}), ...details }), [current?.personal_details, details]);
  const locked = ['submitted', 'under_review', 'approved'].includes(current?.status || '');

  const saveDraft = useMutation({ mutationFn: () => apiFetch('/kyc/draft', { method: 'POST', body: JSON.stringify(values) }), onSuccess: () => { setNotice(mm ? 'မူကြမ်း သိမ်းပြီးပါပြီ။' : 'Draft saved securely.'); setError(''); void queryClient.invalidateQueries({ queryKey: ['customer-kyc'] }); }, onError: (caught) => setError(message(caught)) });
  const upload = useMutation({ mutationFn: async () => { if (!file || !documentType) throw new Error('Select a configured document type and file.'); const body = new FormData(); body.append('document_type', documentType); body.append('document', file); return apiFetch('/kyc/documents', { method: 'POST', body }); }, onSuccess: () => { setFile(null); setNotice(mm ? 'စာရွက်စာတမ်းကို လက်ခံပြီး scan စစ်ဆေးနေပါပြီ။' : 'Document received and queued for security scanning.'); setError(''); void queryClient.invalidateQueries({ queryKey: ['customer-kyc'] }); }, onError: (caught) => setError(message(caught)) });
  const remove = useMutation({ mutationFn: (id: string) => apiFetch(`/kyc/documents/${id}`, { method: 'DELETE' }), onSuccess: () => { setNotice('Document removed from your draft.'); void queryClient.invalidateQueries({ queryKey: ['customer-kyc'] }); }, onError: (caught) => setError(message(caught)) });
  const submit = useMutation({ mutationFn: () => apiFetch('/kyc/submit', { method: 'POST', body: JSON.stringify({ consent }) }), onSuccess: () => { setNotice(mm ? 'KYC လျှောက်လွှာ တင်ပြီးပါပြီ။' : 'Application submitted for authorized review.'); setError(''); setStep(3); void queryClient.invalidateQueries({ queryKey: ['customer-kyc'] }); }, onError: (caught) => setError(message(caught)) });

  function changeDetail(code: string, value: string) { setDetails((previous) => ({ ...previous, [code]: value })); }
  function next() { setError(''); setNotice(''); if (step === 1) { saveDraft.mutate(); setStep(2); } else if (step === 2) setStep(3); }

  if (kyc.isLoading) return <AppShell><main className="page"><div className="kyc-loading"><span/><span/><span/></div></main></AppShell>;
  if (kyc.isError) return <AppShell><main className="page"><div className="kyc-error"><AlertTriangle size={20}/><div><strong>Unable to load KYC</strong><p>{message(kyc.error)} Sign in again or retry.</p><button className="secondary-btn" onClick={() => void kyc.refetch()}>Retry</button></div></div></main></AppShell>;

  const available = Boolean(policy?.available);
  return <AppShell><main className="page kyc-page"><div className="page-heading"><div><span className="eyebrow">IDENTITY & COMPLIANCE</span><h1>{mm ? 'ကိုယ်ရေးအတည်ပြုခြင်း' : 'Identity verification'}</h1><p className="subtle">{mm ? 'သင့်အကောင့်ကို လုံခြုံစွာ အတည်ပြုရန် အချက်အလက်များ ပေးပို့ပါ။' : 'Submit your identity information securely for authorized review.'}</p></div><span className="demo-badge"><ShieldCheck size={13}/> Sandbox review</span></div><div className="kyc-status-banner"><div className="kyc-status-icon"><ShieldCheck size={19}/></div><div><span className="eyebrow">CURRENT STATUS</span><strong>{statusLabel(current?.status || 'not_started', mm)}</strong><small>{current?.submitted_at ? `Submitted ${new Date(current.submitted_at).toLocaleString()}` : 'No approval is implied by an upload or draft.'}</small></div><span className="kyc-country">{policy?.country_name || policy?.country_code || 'Country not configured'}</span></div>{!available ? <UnavailableKyc policy={policy}/> : <><div className="kyc-progress" aria-label="KYC progress">{[['1', 'Your details'], ['2', 'Documents'], ['3', 'Review']].map(([number, label], index) => <button key={number} className={step > index + 1 || (index === 0 && step === 1) ? 'active' : ''} onClick={() => !locked && setStep(index + 1)} disabled={locked && index < 2}><span>{step > index + 1 ? <CheckCircle2 size={15}/> : number}</span>{label}</button>)}</div><div className="kyc-layout"><section className="kyc-card">{step === 1 && <PersonalStep fields={policy?.required_fields || []} values={values} onChange={changeDetail} locked={locked}/>} {step === 2 && <DocumentsStep documents={current?.documents || []} types={policy?.document_types || []} documentType={documentType} setDocumentType={setDocumentType} file={file} setFile={setFile} onUpload={() => upload.mutate()} onRemove={(id) => remove.mutate(id)} locked={locked} busy={upload.isPending}/>} {step === 3 && <ReviewStep details={values} fields={policy?.required_fields || []} documents={current?.documents || []} consent={consent} setConsent={setConsent} locked={locked}/>}<div className="kyc-actions">{step > 1 && !locked && <button className="secondary-btn" onClick={() => setStep(step - 1)}>Back</button>}{step < 3 && !locked && <button className="primary-btn" onClick={next}>Continue <ChevronRight size={16}/></button>}{step === 3 && !locked && <button className="primary-btn" disabled={!consent || submit.isPending} onClick={() => submit.mutate()}>{submit.isPending ? 'Submitting…' : 'Submit for review'} <FileCheck2 size={16}/></button>}</div>{notice && <p className="kyc-notice" role="status"><CheckCircle2 size={15}/>{notice}</p>}{error && <p className="kyc-error-inline" role="alert"><AlertTriangle size={15}/>{error}</p>}</section><aside className="kyc-side"><div className="kyc-info-card"><LockKeyhole size={18}/><h2>Private by design</h2><p>Your information is encrypted at rest, stored privately, and visible only to authorized review staff. We never publish document URLs.</p></div><div className="kyc-info-card"><ShieldCheck size={18}/><h2>Review policy</h2><p>Document upload is not approval. Screening and compliance review must complete before restricted financial capabilities become available.</p></div><div className="kyc-help"><strong>Need help?</strong><span>Contact support without sharing passwords or recovery codes.</span><a href="/app/support">Open support</a></div></aside></div></>}</main></AppShell>;
}

function UnavailableKyc({ policy }: { policy?: KycResponse['data']['policy'] }) { return <div className="kyc-unavailable"><span className="kyc-unavailable-icon"><ShieldCheck size={25}/></span><h2>KYC is not available yet</h2><p>{policy?.eligible ? 'Your country is eligible, but the required KYC policy has not been configured by the compliance team.' : 'KYC availability is controlled by country policy. This account cannot start verification until an approved policy is enabled.'}</p><div className="kyc-policy-list"><span><CheckCircle2 size={15}/> No customer approval is fabricated</span><span><CheckCircle2 size={15}/> Financial restrictions remain server-enforced</span><span><CheckCircle2 size={15}/> Contact support if your country details are incorrect</span></div><a className="primary-btn" href="/app/support">Contact support</a></div>; }
function PersonalStep({ fields, values, onChange, locked }: { fields: FieldPolicy[]; values: Record<string, string>; onChange: (code: string, value: string) => void; locked: boolean }) { return <div><div className="kyc-section-title"><span className="kyc-step-number">1</span><div><h2>Personal information</h2><p>Enter details exactly as shown on your identity document.</p></div></div><div className="kyc-fields">{fields.map((field) => <label key={field.code}><span>{field.label || labels[field.code]?.en || field.code}{field.required && <i>*</i>}</span><input type={field.code === 'date_of_birth' ? 'date' : 'text'} value={values[field.code] || ''} disabled={locked} onChange={(event) => onChange(field.code, event.target.value)} /></label>)}</div>{!fields.length && <div className="kyc-policy-warning"><AlertTriangle size={17}/><span>No personal-field policy is configured for this country.</span></div>}</div>; }
function DocumentsStep({ documents, types, documentType, setDocumentType, file, setFile, onUpload, onRemove, locked, busy }: { documents: KycDocument[]; types: DocumentPolicy[]; documentType: string; setDocumentType: (value: string) => void; file: File | null; setFile: (file: File | null) => void; onUpload: () => void; onRemove: (id: string) => void; locked: boolean; busy: boolean }) { return <div><div className="kyc-section-title"><span className="kyc-step-number">2</span><div><h2>Identity documents</h2><p>Accepted types and limits come from your country policy.</p></div></div><div className="kyc-upload-box"><label><span>Document type</span><select value={documentType} disabled={locked} onChange={(event) => setDocumentType(event.target.value)}><option value="">Select document type</option>{types.map((type) => <option key={type.code} value={type.code}>{type.label}</option>)}</select></label><label className="file-picker"><Upload size={18}/><span>{file ? file.name : 'Choose PDF, JPG or PNG · max 10 MB'}</span><input type="file" accept="application/pdf,image/jpeg,image/png" disabled={locked} onChange={(event) => setFile(event.target.files?.[0] || null)}/></label>{file && <button className="primary-btn" disabled={busy || locked} onClick={onUpload}>{busy ? 'Uploading…' : 'Upload securely'} <Upload size={15}/></button>}</div><div className="kyc-document-list">{documents.map((document) => <div className="kyc-document" key={document.id}><FileText size={18}/><span><strong>{document.document_type}</strong><small>{document.mime_type} · {document.scan_status} scan · {document.review_status}</small></span>{!locked && document.review_status === 'pending' && <button className="icon-btn" onClick={() => onRemove(document.id)} aria-label="Remove document"><X size={16}/></button>}</div>)}{!documents.length && <p className="kyc-empty">No documents uploaded yet.</p>}</div></div>; }
function ReviewStep({ details, fields, documents, consent, setConsent, locked }: { details: Record<string, string>; fields: FieldPolicy[]; documents: KycDocument[]; consent: boolean; setConsent: (value: boolean) => void; locked: boolean }) { return <div><div className="kyc-section-title"><span className="kyc-step-number">3</span><div><h2>Review and submit</h2><p>Check your information before sending it to authorized reviewers.</p></div></div><div className="kyc-review-list">{fields.map((field) => <div key={field.code}><span>{field.label}</span><strong>{details[field.code] || 'Not provided'}</strong></div>)}</div><div className="kyc-review-docs"><strong>Documents</strong>{documents.map((document) => <span key={document.id}><FileCheck2 size={14}/>{document.document_type} · queued for scan</span>)}</div>{!locked && <label className="kyc-consent"><input type="checkbox" checked={consent} onChange={(event) => setConsent(event.target.checked)}/><span>I consent to identity verification and understand that submission does not guarantee approval.</span></label>}</div>; }
