export const people = ['John', 'Sierra'];
export const rules = {
  'On-time delivery': 5, 'Perfect parking': 5, 'No GPS mistakes': 5,
  'Hard braking': -5, 'Hard acceleration': -5, 'Missed turn': -10,
  'Wrong turn': -10, 'Missed delivery instructions': -10, 'Wrong address': -15,
  'Driver-caused cancellation': -20, 'Something genuinely impressive': 10
};
export const driverFor = completed => people[Math.floor(completed / 3) % 2];
export const signed = value => value > 0 ? '+' + value : String(value);

export function calculate(events) {
  const totals = { John: 150, Sierra: 150 };
  const deliveries = { John: 0, Sierra: 0 };
  let completed = 0;
  const entries = events.map(event => {
    const order = completed + 1;
    if (event.type === 'order') {
      const player = driverFor(completed);
      completed += 1;
      deliveries[player] += 1;
      totals[player] += 10;
      return { ...event, order, player, points: 10, label: 'Delivery completed' };
    }
    const { player, reason } = event;
    const points = rules[reason];
    totals[player] += points;
    return { ...event, order, points, label: reason };
  });
  return { completed, totals, deliveries, entries };
}

