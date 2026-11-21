import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');
const token = __ENV.API_TOKEN || '';

export const options = {
  vus: Number(__ENV.VUS || 5),
  duration: __ENV.DURATION || '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<750'],
  },
};

export default function () {
  const health = http.get(`${baseUrl}/up`);
  check(health, { 'health endpoint returns 200': (response) => response.status === 200 });

  if (token) {
    const tickets = http.get(`${baseUrl}/api/v1/tickets?per_page=15`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    });
    check(tickets, { 'authenticated ticket API returns 200': (response) => response.status === 200 });
  }

  sleep(1);
}
