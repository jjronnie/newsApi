export interface Topic {
  id: number;
  topic_title: string;
  focus_keyword: string;
  description: string | null;
  status: 'pending' | 'used' | 'rejected';
  created_at: string;
}
